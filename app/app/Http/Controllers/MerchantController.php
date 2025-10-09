<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Merchant;
use App\Models\SallaActionAudit;
use App\Models\WebhookEvent;
use App\Services\Salla\WebhookForwarder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MerchantController extends Controller
{
    public function __construct(
        private WebhookForwarder $forwarder
    ) {
    }

    public function dashboard()
    {
        $merchant = $this->currentMerchant();
        $allowTestMode = $this->allowTestMode();
        $connection = $this->connectionStatus($merchant);

        $recentWebhooks = $merchant->webhookEvents()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $recentActions = SallaActionAudit::where('merchant_id', $merchant->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('merchant.dashboard', compact(
            'merchant',
            'recentWebhooks',
            'recentActions',
            'allowTestMode',
            'connection'
        ));
    }

    public function connectSalla()
    {
        $merchant = $this->currentMerchant(required: false);

        return view('merchant.connect-salla', [
            'merchant' => $merchant,
            'connection' => $this->connectionStatus($merchant),
        ]);
    }

    public function claimSalla(Request $request)
    {
        $user = Auth::guard('merchant')->user();
        if (! $user) {
            abort(403);
        }

        $validated = $request->validate([
            'store_domain' => ['required', 'string'],
        ]);

        $identifier = trim($validated['store_domain']);
        $normalizedDomain = $this->normalizeDomain($identifier);

        $candidates = Merchant::query()
            ->whereNull('claimed_by_user_id')
            ->where(function ($query) use ($identifier, $normalizedDomain) {
                $query->where('salla_merchant_id', $identifier);

                if ($normalizedDomain) {
                    $query->orWhere('store_domain', $normalizedDomain)
                        ->orWhere('store_domain', 'like', $normalizedDomain.'%');
                }
            })
            ->get();

        if ($candidates->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'No matching Salla store found. Verify the domain or authorize the app again.');
        }

        if ($candidates->count() > 1) {
            return back()
                ->withInput()
                ->with('error', 'Multiple stores matched this identifier. Please contact support for assistance.');
        }

        $merchantToClaim = $candidates->first();

        if (! $this->hasSallaTokens($merchantToClaim)) {
            return back()
                ->withInput()
                ->with('error', 'This store has not authorized the app yet. Complete the Salla authorization flow first.');
        }

        DB::transaction(function () use ($user, $merchantToClaim) {
            $current = $user->merchant;

            if ($current && $current->id !== $merchantToClaim->id) {
                $current->claimed_by_user_id = null;
                $current->save();
            }

            $merchantToClaim->claimed_by_user_id = $user->id;
            $merchantToClaim->email = $user->email ?? $merchantToClaim->email;
            $merchantToClaim->is_active = true;
            $merchantToClaim->save();

            $user->setRelation('merchant', $merchantToClaim);
        });

        return redirect()
            ->route('settings.connect-salla')
            ->with('success', 'Your Salla store is now linked to this account.');
    }

    public function n8nSettings()
    {
        $merchant = $this->currentMerchant();

        return view('merchant.n8n-settings', [
            'merchant' => $merchant,
            'allowTestMode' => $this->allowTestMode(),
            'connection' => $this->connectionStatus($merchant),
        ]);
    }

    public function updateN8nSettings(Request $request)
    {
        $merchant = $this->currentMerchant();

        $validated = $request->validate([
            'n8n_base_url' => ['required', 'url'],
            'n8n_webhook_path' => ['nullable', 'string'],
            'n8n_auth_type' => ['required', 'in:none,bearer,basic'],
            'n8n_bearer_token' => ['nullable', 'string'],
            'n8n_basic_user' => ['nullable', 'string'],
            'n8n_basic_pass' => ['nullable', 'string'],
        ]);

        $authToken = null;
        if ($validated['n8n_auth_type'] === 'bearer') {
            $authToken = $validated['n8n_bearer_token'] ?: null;
        } elseif ($validated['n8n_auth_type'] === 'basic') {
            if (! $validated['n8n_basic_user'] || ! $validated['n8n_basic_pass']) {
                return back()->withInput()->with('error', 'Basic auth requires both username and password.');
            }

            $authToken = json_encode([
                'username' => $validated['n8n_basic_user'],
                'password' => $validated['n8n_basic_pass'],
            ]);
        }

        $webhookPath = $validated['n8n_webhook_path'] ?? null;
        if ($webhookPath) {
            $webhookPath = '/' . ltrim($webhookPath, '/');
        }

        $merchant->fill([
            'n8n_base_url' => rtrim($validated['n8n_base_url'], '/'),
            'n8n_webhook_path' => $webhookPath,
            'n8n_auth_type' => $validated['n8n_auth_type'],
            'n8n_auth_token' => $authToken,
        ]);

        $merchant->save();

        return redirect()->route('settings.n8n')->with('success', 'n8n settings updated successfully!');
    }

    public function sendTestWebhook(Request $request)
    {
        if (! $this->allowTestMode()) {
            return back()->with('warning', 'Test mode is disabled by the administrator.');
        }

        $merchant = $this->currentMerchant();

        if (! $merchant->is_approved) {
            return back()->with('warning', 'Your account must be approved before sending test webhooks.');
        }

        if (! $this->hasSallaTokens($merchant)) {
            return back()->with('warning', 'Connect your Salla store before sending test webhooks.');
        }

        if (! $merchant->n8n_base_url) {
            return back()->with('warning', 'Please configure your n8n URL first.');
        }

        $eventData = [
            'merchant_id' => $merchant->id,
            'salla_event' => 'order.created',
            'salla_event_id' => 'test_' . Str::uuid(),
            'headers' => ['X-Test' => 'true'],
            'payload' => [
                'event' => 'order.created',
                'id' => 'test_' . now()->timestamp,
                'data' => [
                    'id' => 12345,
                    'number' => 'TEST-ORDER-001',
                    'status' => 'pending',
                    'store' => [
                        'id' => $merchant->salla_merchant_id,
                        'name' => $merchant->store_name,
                    ],
                ],
            ],
            'status' => 'stored',
        ];

        if (Schema::hasColumn('webhook_events', 'salla_merchant_id')) {
            $eventData['salla_merchant_id'] = $merchant->salla_merchant_id;
        }

        $testEvent = WebhookEvent::create($eventData);

        $result = $this->forwarder->forward($testEvent, $merchant);

        $testEvent->forceFill([
            'status' => $result['ok'] ? 'sent' : 'failed',
            'response_status' => $result['code'],
            'response_body_excerpt' => $result['body'],
            'last_error' => $result['ok'] ? null : ($result['error'] ?? null),
            'attempts' => ($testEvent->attempts ?? 0) + ($result['attempts'] ?? 1),
            'forwarded_at' => now(),
        ])->save();

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'Test webhook sent successfully!'
                : 'Failed to send test webhook. Check your n8n configuration.'
        );
    }

    public function webhooks(Request $request)
    {
        $merchant = $this->currentMerchant();

        $webhooks = $merchant->webhookEvents()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('merchant.webhooks', [
            'merchant' => $merchant,
            'webhooks' => $webhooks,
            'allowTestMode' => $this->allowTestMode(),
            'connection' => $this->connectionStatus($merchant),
        ]);
    }

    public function actionsAudit(Request $request)
    {
        $merchant = $this->currentMerchant();

        $actions = SallaActionAudit::where('merchant_id', $merchant->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('merchant.actions-audit', [
            'actions' => $actions,
            'connection' => $this->connectionStatus($merchant),
        ]);
    }

    private function currentMerchant(bool $required = true): ?Merchant
    {
        $user = Auth::guard('merchant')->user();
        $merchant = $user?->merchant;

        if (! $merchant && $required) {
            throw new HttpResponseException(
                redirect()
                    ->route('settings.connect-salla')
                    ->with('warning', 'Connect your Salla store to continue.')
            );
        }

        return $merchant;
    }

    private function allowTestMode(): bool
    {
        return AppSetting::get('ALLOW_TEST_MODE', '0') === '1';
    }

    private function hasSallaTokens(?Merchant $merchant): bool
    {
        if (! $merchant || ! $merchant->salla_access_token) {
            return false;
        }

        return ! $merchant->salla_token_expires_at
            || $merchant->salla_token_expires_at->isFuture();
    }

    private function hasN8nConfig(?Merchant $merchant): bool
    {
        if (! $merchant) {
            return false;
        }

        return ! empty($merchant->n8n_base_url);
    }

    private function connectionStatus(?Merchant $merchant): array
    {
        return [
            'is_claimed' => (bool) ($merchant?->claimed_by_user_id),
            'has_tokens' => $this->hasSallaTokens($merchant),
            'n8n_configured' => $this->hasN8nConfig($merchant),
            'actions_ready' => $this->hasSallaTokens($merchant),
        ];
    }

    private function normalizeDomain(?string $domain): ?string
    {
        if (! $domain) {
            return null;
        }

        $clean = Str::of($domain)
            ->trim()
            ->lower()
            ->replace(['https://', 'http://'], '')
            ->before('/');

        return $clean->isEmpty() ? null : $clean->toString();
    }
}
