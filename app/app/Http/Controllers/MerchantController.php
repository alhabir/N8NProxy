<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\SallaActionAudit;
use App\Models\WebhookEvent;
use App\Services\Salla\WebhookForwarder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $recentWebhooks = $merchant->webhookEvents()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $recentActions = SallaActionAudit::where('merchant_id', $merchant->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('merchant.dashboard', compact('merchant', 'recentWebhooks', 'recentActions', 'allowTestMode'));
    }

    public function n8nSettings()
    {
        $merchant = $this->currentMerchant();

        return view('merchant.n8n-settings', [
            'merchant' => $merchant,
            'allowTestMode' => $this->allowTestMode(),
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

        $success = $this->forwarder->forward($testEvent, $merchant);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Test webhook sent successfully!' : 'Failed to send test webhook. Check your n8n configuration.'
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
        ]);
    }

    public function actionsAudit(Request $request)
    {
        $merchant = $this->currentMerchant();

        $actions = SallaActionAudit::where('merchant_id', $merchant->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('merchant.actions-audit', compact('actions'));
    }

    private function currentMerchant()
    {
        $user = Auth::guard('merchant')->user();

        $merchant = $user?->merchant;

        if (! $merchant) {
            abort(403, 'Merchant profile not found.');
        }

        return $merchant;
    }

    private function allowTestMode(): bool
    {
        return AppSetting::get('ALLOW_TEST_MODE', '0') === '1';
    }
}
