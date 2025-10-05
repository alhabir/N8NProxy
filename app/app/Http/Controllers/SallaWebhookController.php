<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\WebhookEvent;
use App\Services\Forwarder;
use App\Services\Salla\SignatureValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SallaWebhookController extends Controller
{
    public function ingest(Request $request)
    {
        $raw = $request->getContent();
        $headers = collect($request->headers->all())
            ->map(fn($v) => is_array($v) ? ($v[0] ?? null) : $v)
            ->toArray();

        [$sigOk, $sigError] = SignatureValidator::validate($headers, $raw, (string) env('SALLA_WEBHOOK_SECRET', ''));

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return response()->json(['error' => 'invalid_json'], 400);
        }

        $eventPath = config('salla.paths.event_name');
        $eventName = data_get($payload, $eventPath);
        if (!$eventName) {
            return response()->json(['error' => 'event_name missing'], 400);
        }

        if (!in_array($eventName, config('salla.supported_events', []), true)) {
            return response()->json(['accepted' => false, 'error' => 'unsupported_event'], 202);
        }

        $paths = config('salla.paths');
        foreach (config('salla.paths_overrides', []) as $pattern => $override) {
            if (Str::is($pattern, $eventName)) {
                $paths = array_merge($paths, $override);
            }
        }

        $sallaEventId = data_get($payload, $paths['event_id']);
        if (!$sallaEventId) {
            $sallaEventId = hash('sha256', json_encode(['h' => $headers, 'b' => $raw]));
        }

        $merchantId = data_get($payload, $paths['merchant_id']);
        if (!$merchantId) {
            return response()->json(['error' => 'merchant_id missing'], 400);
        }

        $merchant = Merchant::where('salla_merchant_id', (string) $merchantId)->first();

        $existing = WebhookEvent::where('salla_event_id', $sallaEventId)->first();
        if ($existing) {
            return response()->json(['accepted' => true, 'duplicate' => true, 'id' => $existing->id]);
        }

        if (!$sigOk) {
            logger()->warning('Salla webhook signature validation failed', [
                'salla_event_id' => $sallaEventId,
                'error' => $sigError,
            ]);

            return response()->json([
                'error' => 'invalid_signature',
                'reason' => $sigError,
            ], 401);
        }

        $event = WebhookEvent::create([
            'salla_event' => (string) $eventName,
            'salla_event_id' => (string) $sallaEventId,
            'salla_merchant_id' => (string) $merchantId,
            'headers' => $headers,
            'payload' => $payload,
            'status' => 'stored',
        ]);

        if (!$merchant || !$merchant->is_active || empty($merchant->n8n_base_url)) {
            $event->update([
                'status' => 'skipped',
                'last_error' => $merchant ? 'inactive_merchant' : 'merchant_not_found',
            ]);
            return response()->json([
                'accepted' => true,
                'duplicate' => false,
                'status' => $event->status,
                'id' => $event->id,
            ]);
        }

        $forwarder = new Forwarder();
        $result = $forwarder->forward($event, $merchant);

        $event->update([
            'attempts' => $event->attempts + ($result['attempts'] ?? 1),
            'last_error' => $result['error'] ?? null,
            'status' => $result['ok'] ? 'sent' : 'failed',
        ]);

        return response()->json([
            'accepted' => true,
            'duplicate' => false,
            'status' => $event->status,
            'id' => $event->id,
        ]);
    }

    public function health()
    {
        try {
            \DB::select('select 1');
            $dbOk = true;
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        return response()->json([
            'status' => 'ok',
            'time' => now()->toIso8601String(),
            'db_ok' => $dbOk,
        ]);
    }

    public function test(Request $request)
    {
        if (!filter_var(env('ALLOW_TEST_MODE', false), FILTER_VALIDATE_BOOL)) {
            abort(403);
        }

        return $this->ingest($request);
    }
}


