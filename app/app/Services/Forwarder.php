<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
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

        while ($attempts <= $retries) {
            $attempts++;
            try {
                $response = Http::withHeaders($headers)
                    ->timeout(max(1, (int) ceil($timeoutMs / 1000)))
                    ->asJson()
                    ->post($url, $payload);

                $status = $response->status();
                if ($status >= 200 && $status < 300) {
                    return [
                        'ok' => true,
                        'code' => $status,
                        'body' => Str::limit($response->body(), 65535, ''),
                        'attempts' => $attempts,
                    ];
                }

                if (!in_array($status, [408, 429]) && $status < 500) {
                    return [
                        'ok' => false,
                        'code' => $status,
                        'body' => Str::limit($response->body(), 65535, ''),
                        'attempts' => $attempts,
                        'error' => 'non_retryable_status',
                    ];
                }
                $lastError = 'retryable_status_'.$status;
            } catch (\Throwable $e) {
                $lastError = 'network_exception: '.$e->getMessage();
            }
        }

        return [
            'ok' => false,
            'code' => $response?->status(),
            'body' => $response ? Str::limit($response->body(), 65535, '') : null,
            'attempts' => $attempts,
            'error' => $lastError ?? 'unknown',
        ];
    }
}

