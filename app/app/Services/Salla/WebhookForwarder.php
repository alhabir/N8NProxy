<?php

namespace App\Services\Salla;

use App\Models\ForwardingAttempt;
use App\Models\Merchant;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookForwarder
{
    public function forward(WebhookEvent $event, Merchant $merchant): array
    {
        $targetUrl = $this->buildTargetUrl($merchant);

        if (! $targetUrl) {
            Log::warning('Salla webhook forward skipped: missing target URL', [
                'event_id' => $event->id,
                'merchant_id' => $merchant->id,
            ]);

            return [
                'ok' => false,
                'code' => null,
                'body' => null,
                'error' => 'missing_target',
                'attempts' => 0,
            ];
        }

        $headers = $this->buildHeaders($event, $merchant);
        $body = json_encode($event->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $timeoutMs = (int) env('FORWARD_DEFAULT_TIMEOUT_MS', 6000);
        $timeoutSeconds = max(1, (int) ceil($timeoutMs / 1000));

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeoutSeconds)
                ->withBody($body ?: '{}', 'application/json')
                ->post($targetUrl);

            $duration = (microtime(true) - $startTime) * 1000;
            $bodyExcerpt = Str::limit($response->body(), 2000, '');

            $this->logAttempt($event, $targetUrl, $response->status(), $bodyExcerpt, $duration);

            if ($response->successful()) {
                Log::info('Salla webhook forwarded successfully', [
                    'event_id' => $event->id,
                    'merchant_id' => $merchant->id,
                    'status' => $response->status(),
                ]);

                return [
                    'ok' => true,
                    'code' => $response->status(),
                    'body' => $bodyExcerpt,
                    'attempts' => 1,
                ];
            }

            Log::warning('Salla webhook forward returned non-success status', [
                'event_id' => $event->id,
                'merchant_id' => $merchant->id,
                'status' => $response->status(),
            ]);

            return [
                'ok' => false,
                'code' => $response->status(),
                'body' => $bodyExcerpt,
                'error' => 'http_'.$response->status(),
                'attempts' => 1,
            ];
        } catch (\Throwable $exception) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logAttempt($event, $targetUrl, 0, $exception->getMessage(), $duration);

            Log::error('Salla webhook forward failed', [
                'event_id' => $event->id,
                'merchant_id' => $merchant->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'code' => null,
                'body' => null,
                'error' => 'exception: '.$exception->getMessage(),
                'attempts' => 1,
            ];
        }
    }

    private function buildTargetUrl(Merchant $merchant): ?string
    {
        if (! $merchant->n8n_base_url) {
            return null;
        }

        $base = rtrim($merchant->n8n_base_url, '/');
        $path = $merchant->n8n_webhook_path ?: '/webhook/salla';
        $path = '/' . ltrim($path, '/');

        return $base . $path;
    }

    private function buildHeaders(WebhookEvent $event, Merchant $merchant): array
    {
        $forwardedBy = config('salla.forwarding.forwarded_by', 'N8NProxy');
        $merchantHeader = config('salla.forwarding.merchant_header', 'X-N8NProxy-Merchant-ID');

        $headers = [
            'Content-Type' => 'application/json',
            'X-Forwarded-By' => $forwardedBy,
            $merchantHeader => $merchant->id,
        ];

        $headerConfig = config('salla.headers');
        $eventHeaders = $event->headers ?? [];

        $eventHeaderName = $headerConfig['event'] ?? 'X-Salla-Event';
        $eventIdHeaderName = $headerConfig['event_id'] ?? 'X-Salla-Event-Id';
        $merchantIdHeaderName = $headerConfig['merchant'] ?? 'X-Salla-Merchant-Id';

        $headers[$eventHeaderName] = $this->headerValue($eventHeaders, $eventHeaderName) ?: $event->salla_event;
        $headers[$eventIdHeaderName] = $this->headerValue($eventHeaders, $eventIdHeaderName) ?: $event->salla_event_id;
        $headers[$merchantIdHeaderName] = $this->headerValue($eventHeaders, $merchantIdHeaderName) ?: $event->salla_merchant_id;

        if ($merchant->n8n_auth_type === 'bearer' && $merchant->n8n_auth_token) {
            $headers['Authorization'] = 'Bearer '.$merchant->n8n_auth_token;
        } elseif ($merchant->n8n_auth_type === 'basic' && $merchant->n8n_auth_token) {
            $credentials = $this->decodeBasicCredentials($merchant->n8n_auth_token);
            if ($credentials) {
                $headers['Authorization'] = 'Basic '.base64_encode($credentials['username'].':'.$credentials['password']);
            }
        }

        return $headers;
    }

    private function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (Str::lower($key) === Str::lower($name)) {
                return is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        return null;
    }

    private function decodeBasicCredentials(string $token): ?array
    {
        $decoded = json_decode($token, true);

        if (! is_array($decoded)) {
            return null;
        }

        $username = $decoded['username'] ?? null;
        $password = $decoded['password'] ?? null;

        if (! is_string($username) || $username === '' || ! is_string($password) || $password === '') {
            return null;
        }

        return [
            'username' => $username,
            'password' => $password,
        ];
    }

    private function logAttempt(WebhookEvent $event, string $targetUrl, int $status, ?string $body, float $duration): void
    {
        ForwardingAttempt::create([
            'webhook_event_id' => $event->id,
            'target_url' => $targetUrl,
            'response_status' => $status,
            'response_body' => $body,
            'duration_ms' => (int) $duration,
        ]);
    }
}
