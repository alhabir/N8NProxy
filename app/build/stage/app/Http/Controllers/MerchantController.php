<?php

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use App\Models\SallaActionAudit;
use App\Services\Salla\WebhookForwarder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantController extends Controller
{
    public function __construct(
        private WebhookForwarder $forwarder
    ) {}

    public function dashboard()
    {
        $merchant = Auth::user();
        
        $recentWebhooks = WebhookEvent::where('salla_merchant_id', $merchant->salla_merchant_id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $recentActions = SallaActionAudit::where('merchant_id', $merchant->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('merchant.dashboard', compact('merchant', 'recentWebhooks', 'recentActions'));
    }

    public function n8nSettings()
    {
        $merchant = Auth::user();
        return view('merchant.n8n-settings', compact('merchant'));
    }

    public function updateN8nSettings(Request $request)
    {
        $merchant = Auth::user();
        
        $request->validate([
            'n8n_base_url' => 'required|url',
            'n8n_path' => 'nullable|string',
            'n8n_auth_type' => 'required|in:none,bearer,basic',
            'n8n_bearer_token' => 'nullable|string',
            'n8n_basic_user' => 'nullable|string',
            'n8n_basic_pass' => 'nullable|string',
        ]);

        $merchant->update($request->only([
            'n8n_base_url',
            'n8n_path',
            'n8n_auth_type',
            'n8n_bearer_token',
            'n8n_basic_user',
            'n8n_basic_pass',
        ]));

        return redirect()->route('settings.n8n')->with('success', 'n8n settings updated successfully!');
    }

    public function sendTestWebhook(Request $request)
    {
        $merchant = Auth::user();
        
        if (!$merchant->n8n_base_url) {
            return back()->with('error', 'Please configure your n8n URL first.');
        }

        // Create a test webhook event
        $testEvent = WebhookEvent::create([
            'salla_event' => 'order.created',
            'salla_event_id' => 'test_' . time(),
            'salla_merchant_id' => $merchant->salla_merchant_id,
            'headers' => ['X-Test' => 'true'],
            'payload' => [
                'event' => 'order.created',
                'id' => 'test_' . time(),
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
        ]);

        // Forward the test event
        $success = $this->forwarder->forward($testEvent, $merchant);

        if ($success) {
            return back()->with('success', 'Test webhook sent successfully!');
        } else {
            return back()->with('error', 'Failed to send test webhook. Check your n8n configuration.');
        }
    }

    public function webhooks(Request $request)
    {
        $merchant = Auth::user();
        
        $webhooks = WebhookEvent::where('salla_merchant_id', $merchant->salla_merchant_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('merchant.webhooks', compact('webhooks'));
    }

    public function actionsAudit(Request $request)
    {
        $merchant = Auth::user();
        
        $actions = SallaActionAudit::where('merchant_id', $merchant->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('merchant.actions-audit', compact('actions'));
    }
}
