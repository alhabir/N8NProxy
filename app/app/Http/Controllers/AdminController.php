<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\WebhookEvent;
use App\Models\SallaActionAudit;
use App\Models\AppSetting;
use App\Services\Salla\WebhookForwarder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct(
        private WebhookForwarder $forwarder
    ) {}

    public function index()
    {
        $stats = [
            'total_merchants' => Merchant::count(),
            'approved_merchants' => Merchant::where('is_approved', true)->count(),
            'pending_merchants' => Merchant::where('is_approved', false)->count(),
            'total_webhooks' => WebhookEvent::count(),
            'total_actions' => SallaActionAudit::count(),
        ];

        $recentWebhooks = WebhookEvent::orderBy('created_at', 'desc')->limit(10)->get();
        $recentActions = SallaActionAudit::orderBy('created_at', 'desc')->limit(10)->get();

        return view('admin.index', compact('stats', 'recentWebhooks', 'recentActions'));
    }

    public function merchants(Request $request)
    {
        $query = Merchant::query();
        
        if ($request->has('search')) {
            $query->where('store_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }
        
        if ($request->has('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }

        $merchants = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.merchants', compact('merchants'));
    }

    public function approveMerchant(Request $request, Merchant $merchant)
    {
        $merchant->update(['is_approved' => true]);
        
        return back()->with('success', "Merchant {$merchant->store_name} has been approved.");
    }

    public function appSettings()
    {
        $settings = AppSetting::all()->keyBy('key');
        return view('admin.app-settings', compact('settings'));
    }

    public function updateAppSettings(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'App settings updated successfully!');
    }

    public function webhooks(Request $request)
    {
        $query = WebhookEvent::query();
        
        if ($request->has('merchant_id')) {
            $query->where('salla_merchant_id', $request->merchant_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $webhooks = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.webhooks', compact('webhooks'));
    }

    public function actionsAudit(Request $request)
    {
        $query = SallaActionAudit::query();
        
        if ($request->has('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }
        
        if ($request->has('resource')) {
            $query->where('resource', $request->resource);
        }

        $actions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.actions-audit', compact('actions'));
    }

    public function sendTestWebhook(Request $request, Merchant $merchant)
    {
        if (!$merchant->n8n_base_url) {
            return back()->with('error', 'Merchant has no n8n URL configured.');
        }

        // Create a test webhook event
        $testEvent = WebhookEvent::create([
            'salla_event' => 'order.created',
            'salla_event_id' => 'admin_test_' . time(),
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

        // Forward the test event
        $success = $this->forwarder->forward($testEvent, $merchant);

        if ($success) {
            return back()->with('success', "Test webhook sent to {$merchant->store_name} successfully!");
        } else {
            return back()->with('error', "Failed to send test webhook to {$merchant->store_name}.");
        }
    }
}
