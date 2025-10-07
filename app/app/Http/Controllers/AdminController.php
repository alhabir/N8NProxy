<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Merchant;
use App\Models\SallaActionAudit;
use App\Models\WebhookEvent;
use App\Services\Salla\WebhookForwarder;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private WebhookForwarder $forwarder
    ) {
    }

    public function index()
    {
        $counts = [
            'merchants' => Merchant::count(),
            'webhook_events' => WebhookEvent::count(),
            'salla_action_audits' => SallaActionAudit::count(),
        ];

        $recentWebhooks = WebhookEvent::query()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentActions = SallaActionAudit::query()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.index', [
            'counts' => $counts,
            'recentWebhooks' => $recentWebhooks,
            'recentActions' => $recentActions,
        ]);
    }

    public function merchants(Request $request)
    {
        $query = Merchant::query();

        if ($request->has('search')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('store_name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }

        $merchants = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.merchants', compact('merchants'));
    }

    public function approveMerchant(Request $request, Merchant $merchant)
    {
        $merchant->update(['is_approved' => true]);

        return back()->with('success', "Merchant {$merchant->store_name} has been approved.");
    }

    public function appSettings()
    {
        $settingKeys = [
            'ACTIONS_API_BEARER',
            'FORWARD_DEFAULT_TIMEOUT_MS',
            'FORWARD_SYNC_RETRIES',
            'FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS',
            'ALLOW_TEST_MODE',
        ];

        $settings = AppSetting::many($settingKeys);

        return view('admin.app-settings', compact('settings'));
    }

    public function appSettingsSave(Request $request)
    {
        $validated = $request->validate([
            'actions_api_bearer' => ['nullable', 'string'],
            'forward_default_timeout_ms' => ['required', 'integer', 'min:100', 'max:120000'],
            'forward_sync_retries' => ['required', 'integer', 'min:0', 'max:10'],
            'forward_retry_schedule_max_attempts' => ['required', 'integer', 'min:0', 'max:50'],
            'allow_test_mode' => ['boolean'],
        ]);

        $validated['allow_test_mode'] = $request->boolean('allow_test_mode');

        $mapping = [
            'actions_api_bearer' => 'ACTIONS_API_BEARER',
            'forward_default_timeout_ms' => 'FORWARD_DEFAULT_TIMEOUT_MS',
            'forward_sync_retries' => 'FORWARD_SYNC_RETRIES',
            'forward_retry_schedule_max_attempts' => 'FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS',
            'allow_test_mode' => 'ALLOW_TEST_MODE',
        ];

        foreach ($mapping as $input => $key) {
            $value = $validated[$input] ?? null;

            if ($input === 'allow_test_mode') {
                $value = $value ? '1' : '0';
            }

            AppSetting::set($key, $value);
        }

        return back()->with('success', 'App settings updated successfully.');
    }

    public function webhooks()
    {
        $events = WebhookEvent::query()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('admin.webhooks', ['events' => $events]);
    }

    public function actionsAudit()
    {
        $audits = SallaActionAudit::query()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('admin.actions-audit', ['audits' => $audits]);
    }

    public function sendTestWebhook(Request $request, Merchant $merchant)
    {
        if (!$merchant->n8n_base_url) {
            return back()->with('error', 'Merchant has no n8n URL configured.');
        }

        $testEvent = WebhookEvent::create([
            'merchant_id' => $merchant->id,
            'salla_event' => 'order.created',
            'salla_event_id' => 'admin_test_' . now()->timestamp,
            'salla_merchant_id' => $merchant->salla_merchant_id,
            'headers' => ['X-Test' => 'true', 'X-Admin-Test' => 'true'],
            'payload' => [
                'event' => 'order.created',
                'id' => 'admin_test_' . time(),
                'data' => [
                    'id' => 99999,
                    'number' => 'ADMIN-TEST-ORDER',
                    'status' => 'pending',
                    'store' => [
                        'id' => $merchant->salla_merchant_id,
                        'name' => $merchant->store_name,
                    ],
                ],
            ],
            'status' => 'stored',
        ]);

        $success = $this->forwarder->forward($testEvent, $merchant);

        if ($success) {
            return back()->with('success', "Test webhook sent to {$merchant->store_name} successfully!");
        }

        return back()->with('error', "Failed to send test webhook to {$merchant->store_name}.");
    }
}
