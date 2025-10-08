<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Forwarder
{
    public function forward(WebhookEvent $event, Merchant $merchant): array
    {
        $base = rtrim($merchant->n8n_base_url, '/');
        $path = '/'.ltrim($merchant->n8n_webhook_path ?? '/webhook/salla', '/');
        $url = $base.$path;

        $payload = $event->payload;
        $normalized = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $checksum = hash('sha256', $normalized);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Forwarded-By' => 'n8n-ai-salla-proxy',
            'X-Salla-Event' => $event->salla_event,
            'X-Salla-Event-Id' => $event->salla_event_id,
            'X-Salla-Merchant-Id' => $event->salla_merchant_id,
            'X-Event-Checksum' => $checksum,
        ];

        if ($merchant->n8n_auth_type === 'bearer' && $merchant->n8n_auth_token) {
            $headers['Authorization'] = 'Bearer '.$merchant->n8n_auth_token;
        } elseif ($merchant->n8n_auth_type === 'basic' && $merchant->n8n_auth_token) {
            $credentials = json_decode($merchant->n8n_auth_token, true);
            if (
                is_array($credentials)
                && !empty($credentials['username'])
                && array_key_exists('password', $credentials)
            ) {
                $headers['Authorization'] = 'Basic '.base64_encode($credentials['username'].':'.$credentials['password']);
            }
        }

        $timeoutMs = (int) (env('FORWARD_DEFAULT_TIMEOUT_MS', 6000));
        $retries = (int) (env('FORWARD_SYNC_RETRIES', 2));

        $attempts = 0;
        $lastError = null;
        $response = null;

        Log::info('Webhook forwarding attempt queued', [
            'event_id' => $event->id,
            'merchant_id' => $merchant->id,
            'target_url' => $url,
            'auth_type' => $merchant->n8n_auth_type,
        ]);

        while ($attempts <= $retries) {
            $attempts++;
            try {
                $response = Http::withHeaders($headers)
                    ->timeout(max(1, (int) ceil($timeoutMs / 1000)))
                    ->asJson()
                    ->post($url, $payload);

                $status = $response->status();
                if ($status >= 200 && $status < 300) {
                    Log::info('Webhook forwarding succeeded', [
                        'event_id' => $event->id,
                        'merchant_id' => $merchant->id,
                        'target_url' => $url,
                        'response_status' => $status,
                    ]);
                    return [
                        'ok' => true,
                        'code' => $status,
                        'body' => Str::limit($response->body(), 65535, ''),
                        'attempts' => $attempts,
                    ];
                }

                if (!in_array($status, [408, 429]) && $status < 500) {
                    Log::warning('Webhook forwarding returned non-retryable status', [
                        'event_id' => $event->id,
                        'merchant_id' => $merchant->id,
                        'target_url' => $url,
                        'response_status' => $status,
                        'response_body' => Str::limit($response->body(), 2000),
                    ]);
                    return [
                        'ok' => false,
                        'code' => $status,
                        'body' => Str::limit($response->body(), 65535, ''),
                        'attempts' => $attempts,
                        'error' => 'non_retryable_status',
                    ];
                }
                $lastError = 'retryable_status_'.$status;
                Log::warning('Webhook forwarding retry scheduled', [
                    'event_id' => $event->id,
                    'merchant_id' => $merchant->id,
                    'target_url' => $url,
                    'response_status' => $status,
                    'attempt' => $attempts,
                ]);
            } catch (\Throwable $e) {
                $lastError = 'network_exception: '.$e->getMessage();
                Log::error('Webhook forwarding network exception', [
                    'event_id' => $event->id,
                    'merchant_id' => $merchant->id,
                    'target_url' => $url,
                    'error' => $e->getMessage(),
                    'attempt' => $attempts,
                ]);
            }
        }

        Log::error('Webhook forwarding failed after retries', [
            'event_id' => $event->id,
            'merchant_id' => $merchant->id,
            'target_url' => $url,
            'error' => $lastError,
            'response_status' => $response?->status(),
            'response_body' => $response ? Str::limit($response->body(), 2000) : null,
            'attempts' => $attempts,
        ]);

        return [
            'ok' => false,
            'code' => $response?->status(),
            'body' => $response ? Str::limit($response->body(), 65535, '') : null,
            'attempts' => $attempts,
            'error' => $lastError ?? 'unknown',
        ];
    }
}

