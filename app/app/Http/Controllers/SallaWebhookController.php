<?php

namespace App\Http\Controllers;

use App\Models\AppEvent;
use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\WebhookEvent;
use App\Services\Salla\WebhookForwarder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SallaWebhookController extends Controller
{
    public function __construct(
        private WebhookForwarder $forwarder
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent() ?? '';
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            Log::warning('Salla webhook rejected: invalid JSON payload');

            return response()->json([
                'ok' => false,
                'error' => 'invalid_json',
            ], 400);
        }

        $headers = $this->normalizeHeaders($request);
        $eventName = $this->resolveEventName($headers, $payload);

        if (! $eventName) {
            Log::warning('Salla webhook rejected: missing event name', ['headers' => $headers]);

            return response()->json([
                'ok' => false,
                'error' => 'missing_event',
            ], 400);
        }

        $merchantReference = $this->resolveMerchantId($headers, $payload);
        $eventId = $this->resolveEventId($headers, $payload, $rawBody);

        Log::info('Salla webhook received', [
            'event' => $eventName,
            'event_id' => $eventId,
            'merchant_reference' => $merchantReference,
            'is_app_event' => $this->isAppEvent($eventName),
        ]);

        if ($this->isAppEvent($eventName)) {
            $this->logAppEventDebug($request, $payload, $eventName, $merchantReference);
            $this->storeAppEvent($eventName, $payload, $headers, $merchantReference);

            return $this->handleAppEvent($eventName, $payload, $merchantReference);
        }

        return $this->handleStoreEvent(
            $eventName,
            $payload,
            $headers,
            $eventId,
            $merchantReference
        );
    }

    private function handleAppEvent(string $eventName, array $payload, ?string $merchantReference): JsonResponse
    {
        return match ($eventName) {
            'app.store.authorize' => $this->handleAppStoreAuthorize($payload, $merchantReference),
            'app.installed' => $this->handleAppInstalled($payload, $merchantReference),
            'app.uninstalled' => $this->handleAppUninstalled($payload, $merchantReference),
            default => response()->json([
                'ok' => true,
                'kind' => 'app',
                'event' => $eventName,
                'handled' => false,
            ]),
        };
    }

    private function handleAppStoreAuthorize(array $payload, ?string $merchantReference): JsonResponse
    {
        $sallaMerchantId = $merchantReference
            ?? (string) data_get($payload, 'merchant')
            ?? (string) data_get($payload, 'data.store.id');
        $storeName = data_get($payload, 'data.store.name');
        $storeDomain = $this->resolveStoreDomain($payload);
        $storeEmailRaw = data_get($payload, 'data.store.email');
        $storeEmail = is_string($storeEmailRaw) && $storeEmailRaw !== ''
            ? Str::lower($storeEmailRaw)
            : null;
        $tokens = $this->extractTokenPayload($payload);
        $accessToken = $tokens['access'] ?? null;
        $refreshToken = $tokens['refresh'] ?? null;
        $expiresRaw = $tokens['expires'] ?? null;

        if (! $sallaMerchantId) {
            Log::warning('Salla authorize event missing merchant id', [
                'merchant_reference' => $merchantReference,
            ]);

            return response()->json([
                'ok' => false,
                'kind' => 'app',
                'event' => 'app.store.authorize',
                'error' => 'missing_merchant',
            ], 422);
        }

        $tokensSaved = false;
        $expiresAt = null;

        if ($accessToken && $refreshToken) {
            $expiresAt = $this->resolveTokenExpiration($expiresRaw) ?? Carbon::now()->addSeconds(3600);
        }

        DB::transaction(function () use (
            $sallaMerchantId,
            $storeName,
            $storeDomain,
            $storeEmail,
            $accessToken,
            $refreshToken,
            $expiresAt,
            &$tokensSaved
        ) {
            /** @var Merchant $merchant */
            $merchant = Merchant::firstOrNew(['salla_merchant_id' => $sallaMerchantId]);

            if (! $merchant->exists) {
                $merchant->store_id = $merchant->store_id ?: sprintf('salla-%s', $sallaMerchantId);
                $merchant->is_active = true;
            }

            $merchant->store_name = $storeName ?? $merchant->store_name;
            $merchant->store_domain = $storeDomain ?? $merchant->store_domain;
            $merchant->email = $storeEmail ?? $merchant->email;

            if ($accessToken && $refreshToken) {
                $tokensSaved = true;
                $merchant->salla_access_token = $accessToken;
                $merchant->salla_refresh_token = $refreshToken;
                if ($expiresAt) {
                    $merchant->salla_token_expires_at = $expiresAt;
                }
                $merchant->is_approved = true;
                $merchant->is_active = true;
                $merchant->connected_at = $merchant->connected_at ?? Carbon::now();
            }

            $merchant->save();

            if ($tokensSaved) {
                MerchantToken::updateOrCreate(
                    ['salla_merchant_id' => $sallaMerchantId],
                    [
                        'merchant_id' => $merchant->id,
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken,
                        'access_token_expires_at' => $expiresAt,
                    ]
                );
            }
        });

        if (! $tokensSaved) {
            Log::warning('Salla authorize payload missing tokens in known shapes', [
                'salla_merchant_id' => $sallaMerchantId,
            ]);
        }

        Log::info('Salla merchant authorized', [
            'salla_merchant_id' => $sallaMerchantId,
            'store_name' => $storeName,
            'store_domain' => $storeDomain,
            'tokens_saved' => $tokensSaved,
        ]);

        return response()->json([
            'ok' => true,
            'kind' => 'app',
            'event' => 'app.store.authorize',
            'merchant' => $sallaMerchantId,
            'tokens_saved' => $tokensSaved,
            'needs_reauth' => ! $tokensSaved,
        ]);
    }

    private function handleAppInstalled(array $payload, ?string $merchantReference): JsonResponse
    {
        $sallaMerchantId = $merchantReference ?? (string) data_get($payload, 'data.store.id');
        $storeName = data_get($payload, 'data.store.name');
        $storeDomain = $this->resolveStoreDomain($payload);
        $storeEmailRaw = data_get($payload, 'data.store.email');
        $storeEmail = is_string($storeEmailRaw) && $storeEmailRaw !== ''
            ? Str::lower($storeEmailRaw)
            : null;

        if (! $sallaMerchantId) {
            Log::warning('Salla app.installed missing merchant id');

            return response()->json([
                'ok' => false,
                'error' => 'missing_merchant',
            ], 422);
        }

        Merchant::updateOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            [
                'store_name' => $storeName,
                'store_domain' => $storeDomain,
                'email' => $storeEmail,
                'store_id' => sprintf('salla-%s', $sallaMerchantId),
                'is_active' => true,
                'connected_at' => now(),
            ]
        );

        Log::info('Salla merchant installed app', [
            'salla_merchant_id' => $sallaMerchantId,
            'store_name' => $storeName,
        ]);

        return response()->json([
            'ok' => true,
            'kind' => 'app',
            'event' => 'app.installed',
        ]);
    }

    private function handleAppUninstalled(array $payload, ?string $merchantReference): JsonResponse
    {
        $sallaMerchantId = $merchantReference ?? (string) data_get($payload, 'data.store.id');

        if (! $sallaMerchantId) {
            Log::warning('Salla app.uninstalled missing merchant id');

            return response()->json([
                'ok' => true,
                'kind' => 'app',
                'event' => 'app.uninstalled',
                'handled' => false,
            ]);
        }

        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();

        if ($merchant) {
            $merchant->fill([
                'salla_access_token' => null,
                'salla_refresh_token' => null,
                'salla_token_expires_at' => null,
                'is_approved' => false,
                'connected_at' => null,
            ])->save();
        }

        MerchantToken::where('salla_merchant_id', $sallaMerchantId)->delete();

        Log::info('Salla merchant uninstalled app', [
            'salla_merchant_id' => $sallaMerchantId,
        ]);

        return response()->json([
            'ok' => true,
            'kind' => 'app',
            'event' => 'app.uninstalled',
        ]);
    }

    private function handleStoreEvent(
        string $eventName,
        array $payload,
        array $headers,
        string $eventId,
        ?string $merchantReference
    ): JsonResponse {
        if ($existing = WebhookEvent::where('salla_event_id', $eventId)->first()) {
            Log::info('Salla webhook duplicate detected', [
                'event_id' => $eventId,
                'status' => $existing->status,
            ]);

            return response()->json([
                'ok' => true,
                'kind' => 'store',
                'forwarded' => $existing->status === 'sent',
                'duplicate' => true,
            ]);
        }

        $sallaMerchantId = $merchantReference ?? (string) data_get($payload, 'data.store.id');

        if (! $sallaMerchantId) {
            Log::warning('Salla store event missing merchant id', [
                'event_id' => $eventId,
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'missing_merchant',
            ], 400);
        }

        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();

        if (! $merchant) {
            Log::info('Salla store event ignored: merchant not found', [
                'salla_merchant_id' => $sallaMerchantId,
                'event' => $eventName,
            ]);

            return response()->json([
                'ok' => true,
                'kind' => 'store',
                'forwarded' => false,
                'ignored' => true,
                'reason' => 'merchant-not-found',
            ], 202);
        }

        $event = WebhookEvent::create([
            'merchant_id' => $merchant->id,
            'salla_event' => $eventName,
            'salla_event_id' => $eventId,
            'salla_merchant_id' => $sallaMerchantId,
            'headers' => $headers,
            'payload' => $payload,
            'status' => 'stored',
        ]);

        if (! $merchant->n8n_base_url || ! $merchant->n8n_webhook_path) {
            $event->update([
                'status' => 'skipped',
                'last_error' => 'missing_n8n_configuration',
            ]);

            Log::info('Salla store event skipped: missing n8n configuration', [
                'event_id' => $event->id,
                'merchant_id' => $merchant->id,
            ]);

            return response()->json([
                'ok' => true,
                'kind' => 'store',
                'forwarded' => false,
            ]);
        }

        $result = $this->forwarder->forward($event, $merchant);

        $event->forceFill([
            'status' => $result['ok'] ? 'sent' : 'failed',
            'response_status' => $result['code'],
            'response_body_excerpt' => $result['body'],
            'last_error' => $result['ok'] ? null : ($result['error'] ?? null),
            'attempts' => ($event->attempts ?? 0) + ($result['attempts'] ?? 1),
            'forwarded_at' => now(),
        ])->save();

        return response()->json([
            'ok' => true,
            'kind' => 'store',
            'forwarded' => $result['ok'],
        ]);
    }

    private function resolveEventName(array $headers, array $payload): ?string
    {
        $headerName = config('salla.headers.event', 'X-Salla-Event');
        $headerValue = $this->getHeaderValue($headers, $headerName);

        return $headerValue ?: data_get($payload, 'event');
    }

    private function resolveEventId(array $headers, array $payload, string $rawBody): string
    {
        $headerName = config('salla.headers.event_id', 'X-Salla-Event-Id');
        $headerValue = $this->getHeaderValue($headers, $headerName);

        if ($headerValue) {
            return (string) $headerValue;
        }

        $payloadId = data_get($payload, 'id') ?? data_get($payload, 'data.id');
        if ($payloadId) {
            return (string) $payloadId;
        }

        return hash('sha256', $rawBody);
    }

    private function resolveMerchantId(array $headers, array $payload): ?string
    {
        $headerName = config('salla.headers.merchant', 'X-Salla-Merchant-Id');
        $headerValue = $this->getHeaderValue($headers, $headerName);

        $payloadMerchant = data_get($payload, 'merchant');
        $payloadId = data_get($payload, 'data.store.id');

        if ($headerValue) {
            return (string) $headerValue;
        }

        if ($payloadMerchant) {
            return (string) $payloadMerchant;
        }

        return $payloadId ? (string) $payloadId : null;
    }

    private function resolveStoreDomain(array $payload): ?string
    {
        $domain = data_get($payload, 'data.store.domain')
            ?? data_get($payload, 'data.store.domain_name');

        if (! $domain) {
            $url = data_get($payload, 'data.store.url');
            if ($url) {
                $domain = parse_url($url, PHP_URL_HOST);
            }
        }

        return $domain ? Str::lower($domain) : null;
    }

    private function normalizeHeaders(Request $request): array
    {
        return collect($request->headers->all())
            ->map(fn ($value) => is_array($value) ? ($value[0] ?? null) : $value)
            ->filter()
            ->toArray();
    }

    private function getHeaderValue(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (Str::lower($key) === Str::lower($name)) {
                return is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        return null;
    }

    private function isAppEvent(string $eventName): bool
    {
        $prefix = config('salla.events.app_prefix', 'app.');

        return Str::startsWith($eventName, $prefix);
    }

    private function storeAppEvent(string $eventName, array $payload, array $headers, ?string $merchantReference): AppEvent
    {
        $sallaMerchantId = $merchantReference
            ?: $this->resolveMerchantId($headers, $payload)
            ?: data_get($payload, 'data.store.id');

        $merchant = $sallaMerchantId
            ? Merchant::query()->where('salla_merchant_id', $sallaMerchantId)->first()
            : null;

        $eventCreatedAt = $this->resolveAppEventTimestamp($payload);

        $appEvent = AppEvent::create([
            'event_name' => $eventName,
            'salla_merchant_id' => $sallaMerchantId,
            'merchant_id' => $merchant?->id,
            'payload' => $payload,
            'event_created_at' => $eventCreatedAt,
        ]);

        Log::info('Salla app event stored', [
            'event_id' => $appEvent->id,
            'event_name' => $eventName,
            'salla_merchant_id' => $sallaMerchantId,
            'merchant_id' => $merchant?->id,
        ]);

        return $appEvent;
    }

    private function resolveAppEventTimestamp(array $payload): ?Carbon
    {
        $timestamp = data_get($payload, 'created_at')
            ?? data_get($payload, 'data.created_at')
            ?? data_get($payload, 'data.store.created_at')
            ?? data_get($payload, 'timestamp');

        if (! $timestamp) {
            return null;
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Throwable $exception) {
            Log::debug('Unable to parse app event timestamp', [
                'timestamp' => $timestamp,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function logAppEventDebug(Request $request, array $payload, string $eventName, ?string $merchantReference): void
    {
        $headers = $this->encodeForLog($this->headersForDebug($request), 2048);
        $payloadSample = $this->buildPayloadSampleForLog($payload);

        Log::info('Salla webhook received (debug)', [
            'event' => $eventName,
            'merchant_reference' => $merchantReference,
            'headers' => $headers,
            'payload_sample' => $payloadSample,
        ]);
    }

    private function headersForDebug(Request $request): array
    {
        $allowed = [
            'x-salla-event',
            'x-salla-event-id',
            'x-salla-merchant-id',
            'content-type',
            'user-agent',
        ];

        $sanitized = [];

        foreach ($allowed as $header) {
            $value = $request->headers->get($header);

            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $sanitized[$header] = $this->maskTokenLikeValue($value);
        }

        return $sanitized;
    }

    private function buildPayloadSampleForLog(array $payload): string
    {
        $copy = $payload;

        array_walk_recursive($copy, function (&$value) {
            $value = $this->maskTokenLikeValue($value);
        });

        return $this->encodeForLog($copy, 4096);
    }

    private function encodeForLog(array $data, int $limit): string
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            return '{}';
        }

        return $this->truncateForLog($encoded, $limit);
    }

    private function truncateForLog(string $value, int $limit): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return mb_strimwidth($value, 0, $limit, '…', 'UTF-8');
    }

    private function maskTokenLikeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            if (mb_strlen($trimmed, 'UTF-8') >= 20) {
                $prefix = mb_substr($trimmed, 0, 6, 'UTF-8');
                $suffix = mb_substr($trimmed, -4, null, 'UTF-8');

                return $prefix . '***' . $suffix;
            }

            return $trimmed;
        }

        return $value;
    }

    private function extractTokenPayload(array $payload): array
    {
        return [
            'access' => $this->firstNonEmpty($payload, [
                'data.access_token',
                'data.token.access_token',
                'data.tokens.access_token',
                'data.authorization.access_token',
            ]),
            'refresh' => $this->firstNonEmpty($payload, [
                'data.refresh_token',
                'data.token.refresh_token',
                'data.tokens.refresh_token',
                'data.authorization.refresh_token',
            ]),
            'expires' => $this->firstNonEmpty($payload, [
                'data.expires',
                'data.expires_in',
                'data.token.expires',
                'data.token.expires_in',
                'data.tokens.expires',
                'data.tokens.expires_in',
                'data.authorization.expires',
                'data.authorization.expires_in',
            ]),
        ];
    }

    private function firstNonEmpty(array $payload, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            return $value;
        }

        return null;
    }

    private function resolveTokenExpiration(mixed $expires): ?Carbon
    {
        if ($expires instanceof Carbon) {
            return $expires;
        }

        if (is_numeric($expires)) {
            $value = (int) $expires;

            if ($value <= 0) {
                return null;
            }

            $now = Carbon::now();

            if ($value > $now->timestamp + 60) {
                return Carbon::createFromTimestamp($value);
            }

            return $now->addSeconds($value);
        }

        if (is_string($expires)) {
            $trimmed = trim($expires);

            if ($trimmed === '') {
                return null;
            }

            if (is_numeric($trimmed)) {
                return $this->resolveTokenExpiration((int) $trimmed);
            }

            try {
                return Carbon::parse($trimmed);
            } catch (\Throwable $exception) {
                Log::debug('Unable to parse Salla token expires value', [
                    'raw' => $trimmed,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return null;
    }
}
