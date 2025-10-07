<?php

namespace App\Services\Salla;

use App\Models\Merchant;
use App\Models\WebhookEvent;
use App\Models\ForwardingAttempt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookForwarder
{
    public function forward(WebhookEvent $event, Merchant $merchant): bool
    {
        $targetUrl = $this->buildTargetUrl($merchant);

        if (!$targetUrl) {
            $this->logError($event, 'No target URL configured');
            return false;
        }

        $headers = $this->buildHeaders($event, $merchant);
        $payload = $event->payload;

        $startTime = microtime(true);

        try {
            $response = Http::timeout(config('app.forward_timeout', 6))
                ->withHeaders($headers)
                ->post($targetUrl, $payload);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->logAttempt($event, $targetUrl, $response->status(), $response->body(), $duration);

            if ($response->successful()) {
                $event->update(['status' => 'sent']);
                return true;
            } else {
                $this->logError($event, "HTTP {$response->status()}: {$response->body()}");
                return false;
            }

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logAttempt($event, $targetUrl, 0, $e->getMessage(), $duration);
            $this->logError($event, $e->getMessage());
            return false;
        }
    }

    private function buildTargetUrl(Merchant $merchant): ?string
    {
        if (!$merchant->n8n_base_url) {
            return null;
        }

        $baseUrl = rtrim($merchant->n8n_base_url, '/');
        $path = $merchant->n8n_webhook_path ?: '/webhook/salla';
        $path = '/' . ltrim($path, '/');

        return $baseUrl . $path;
    }

    private function buildHeaders(WebhookEvent $event, Merchant $merchant): array
    {
        $payload = json_encode($event->payload);
        $checksum = hash('sha256', $payload);

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
            $credentials = $this->decodeBasicCredentials($merchant->n8n_auth_token);
            if ($credentials) {
                $headers['Authorization'] = 'Basic '.base64_encode($credentials['username'].':'.$credentials['password']);
            }
        }

        return $headers;
    }

    private function logAttempt(WebhookEvent $event, string $targetUrl, int $status, string $body, float $duration): void
    {
        ForwardingAttempt::create([
            'webhook_event_id' => $event->id,
            'target_url' => $targetUrl,
            'response_status' => $status,
            'response_body' => substr($body, 0, 1000), // Truncate long responses
            'duration_ms' => (int) $duration,
        ]);
    }

    private function logError(WebhookEvent $event, string $error): void
    {
        $event->increment('attempts');
        $event->update([
            'status' => 'failed',
            'last_error' => $error,
        ]);

        Log::error('Webhook forwarding failed', [
            'event_id' => $event->id,
            'merchant_id' => $event->merchant_id,
            'error' => $error,
        ]);
    }

    private function decodeBasicCredentials(string $token): ?array
    {
        $decoded = json_decode($token, true);

        if (!is_array($decoded)) {
            return null;
        }

        $username = $decoded['username'] ?? null;
        $password = $decoded['password'] ?? null;

        if (!is_string($username) || !is_string($password) || $username === '' || $password === '') {
            return null;
        }

        return [
            'username' => $username,
            'password' => $password,
        ];
    }
}
