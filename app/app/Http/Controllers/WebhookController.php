<?php

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use App\Models\Merchant;
use App\Services\Salla\SignatureValidator;
use App\Services\Salla\WebhookForwarder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private SignatureValidator $signatureValidator,
        private WebhookForwarder $forwarder
    ) {}

    public function handle(Request $request)
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
        $signature = $request->headers->get($this->signatureValidator->getHeaderName());
        if (!$this->signatureValidator->validate($request->getContent(), $signature)) {
            $reason = $this->signatureValidator->getLastError();

            Log::warning('Invalid webhook signature', [
                'event' => $event,
                'merchant_id' => $merchantId,
                'reason' => $reason,
            ]);

            $status = match ($reason) {
                'missing_secret' => 500,
                default => 401,
            };

            return response()->json([
                'error' => 'invalid_signature',
                'reason' => $reason,
            ], $status);
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
