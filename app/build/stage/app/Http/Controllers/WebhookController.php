<?php

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use App\Models\Merchant;
use App\Services\Salla\SignatureValidator;
use App\Services\Salla\WebhookForwarder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private SignatureValidator $signatureValidator,
        private WebhookForwarder $forwarder
    ) {}

    public function handle(Request $request): Response
    {
        $headers = $request->headers->all();
        $payload = $request->all();
        
        // Extract event information
        $event = $payload['event'] ?? null;
        $eventId = $payload['id'] ?? null;
        $merchantId = $payload['data']['store']['id'] ?? null;

        if (!$event || !$eventId || !$merchantId) {
            Log::warning('Invalid webhook payload', compact('event', 'eventId', 'merchantId'));
            return response('Invalid payload', 400);
        }

        // Validate signature
        $signature = $request->header('X-Salla-Signature');
        if (!$this->signatureValidator->validate($request->getContent(), $signature)) {
            Log::warning('Invalid webhook signature', [
                'event' => $event,
                'merchant_id' => $merchantId,
            ]);
            return response('Invalid signature', 401);
        }

        // Store webhook event
        $webhookEvent = WebhookEvent::create([
            'salla_event' => $event,
            'salla_event_id' => $eventId,
            'salla_merchant_id' => $merchantId,
            'headers' => $headers,
            'payload' => $payload,
            'status' => 'stored',
        ]);

        // Find merchant and forward
        $merchant = Merchant::where('salla_merchant_id', $merchantId)->first();
        
        if ($merchant && $merchant->is_active && $merchant->is_approved) {
            $this->forwarder->forward($webhookEvent, $merchant);
        } else {
            $webhookEvent->update([
                'status' => 'skipped',
                'last_error' => 'Merchant not found or not approved',
            ]);
        }

        return response('OK', 200);
    }
}
