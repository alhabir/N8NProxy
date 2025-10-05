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
    public function __construct(private WebhookForwarder $forwarder) {}

    public function handle(Request $request): Response
    {
        $headers = collect($request->headers->all())
            ->map(fn ($value) => is_array($value) ? ($value[0] ?? null) : $value)
            ->toArray();
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
        [$sigOk, $sigError] = SignatureValidator::validate(
            $headers,
            $request->getContent(),
            (string) config('salla.webhook_secret')
        );

        if (!$sigOk) {
            Log::warning('Invalid webhook signature', [
                'event' => $event,
                'merchant_id' => $merchantId,
                'reason' => $sigError,
            ]);

            return response()->json([
                'error' => 'invalid_signature',
                'reason' => $sigError,
            ], 401);
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
