<?php

namespace App\Services\Salla;

use App\Models\MerchantToken;
use App\Models\SallaActionAudit;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SallaHttpClient
{
    public function __construct(
        private OAuthTokenStore $tokenStore,
        private OAuthRefresher $refresher
    ) {}

    public function makeRequest(string $sallaMerchantId, string $method, string $endpoint, array $data = []): array
    {
        $token = $this->resolveToken($sallaMerchantId);

        $startTime = microtime(true);
        $url = $this->buildUrl($endpoint);

        try {
            $response = $this->sendRequest($token->access_token, $method, $url, $data);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->auditRequest($sallaMerchantId, $method, $endpoint, $data, $response, $duration);

            if ($response->status() === 401) {
                $token = $this->refresher->refreshUsingToken($token);

                $response = $this->sendRequest($token->access_token, $method, $url, $data);

                $duration = (microtime(true) - $startTime) * 1000;
                $this->auditRequest($sallaMerchantId, $method, $endpoint, $data, $response, $duration);
            }

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'headers' => $response->headers(),
            ];

        } catch (Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->auditRequest($sallaMerchantId, $method, $endpoint, $data, null, $duration, $e->getMessage());

            throw $e;
        }
    }

    private function resolveToken(string $sallaMerchantId): MerchantToken
    {
        $token = $this->tokenStore->getValidToken($sallaMerchantId);

        if ($token) {
            return $token;
        }

        $token = $this->tokenStore->get($sallaMerchantId);

        if (!$token) {
            throw new \Exception("No token found for merchant: {$sallaMerchantId}");
        }

        if ($this->tokenStore->needsRefresh($token)) {
            $token = $this->refresher->refreshUsingToken($token);
        }

        return $token;
    }

    private function sendRequest(string $accessToken, string $method, string $url, array $data = []): Response
    {
        return Http::withToken($accessToken)
            ->timeout(30)
            ->$method($url, $data);
    }

    private function buildUrl(string $endpoint): string
    {
        $base = config('salla_api.base');
        return rtrim($base, '/') . '/' . ltrim($endpoint, '/');
    }

    private function auditRequest(string $sallaMerchantId, string $method, string $endpoint, array $data, $response, float $duration, ?string $error = null): void
    {
        $merchant = \App\Models\Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        
        SallaActionAudit::create([
            'merchant_id' => $merchant?->id,
            'salla_merchant_id' => $sallaMerchantId,
            'resource' => $this->extractResource($endpoint),
            'action' => $this->extractAction($method, $endpoint),
            'method' => strtoupper($method),
            'endpoint' => $endpoint,
            'request_meta' => $this->sanitizeData($data),
            'status_code' => $response?->status(),
            'response_meta' => $response ? $this->sanitizeResponse($response) : null,
            'duration_ms' => (int) $duration,
        ]);
    }

    private function extractResource(string $endpoint): string
    {
        $parts = explode('/', trim($endpoint, '/'));
        return $parts[0] ?? 'unknown';
    }

    private function extractAction(string $method, string $endpoint): string
    {
        $method = strtoupper($method);
        
        if (str_contains($endpoint, '/{id}') || str_contains($endpoint, '/{')) {
            return match($method) {
                'GET' => 'get',
                'PUT', 'PATCH' => 'update',
                'DELETE' => 'delete',
                default => 'unknown'
            };
        }
        
        return match($method) {
            'GET' => 'list',
            'POST' => 'create',
            default => 'unknown'
        };
    }

    private function sanitizeData(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'key'];
        
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $sensitive)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            }
        }
        
        return $data;
    }

    private function sanitizeResponse($response): array
    {
        $body = $response->json();
        if (is_array($body)) {
            $body = $this->sanitizeData($body);
        }
        
        return [
            'body' => $body,
            'headers' => $response->headers(),
        ];
    }
}