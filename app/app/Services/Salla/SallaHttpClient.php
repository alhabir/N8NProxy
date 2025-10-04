<?php

namespace App\Services\Salla;

use App\Models\Merchant;
use App\Models\SallaActionAudit;
use App\Support\Endpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

class SallaHttpClient
{
    public function __construct(
        private OAuthTokenStore $tokens,
        private OAuthRefresher $refresher,
    ) {}

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed>|null $json
     * @param array<string,mixed> $query
     */
    public function call(string $sallaMerchantId, string $method, string $templateUrl, array $params = [], ?array $json = null, array $query = []): Response
    {
        $url = Endpoint::expand($templateUrl, array_merge(['base' => (string) config('salla_api.base')], $params));

        $tokens = $this->ensureFresh($sallaMerchantId);
        $request = Http::withToken($tokens['access'])->acceptJson()->asJson()->timeout(10);

        $start = hrtime(true);
        $response = $this->sendWithRetries($request, $method, $url, $json, $query);

        if ($response->status() === 401) {
            $tokens = $this->refresher->refresh($sallaMerchantId, $tokens['refresh']);
            $this->tokens->updateAccess($sallaMerchantId, $tokens['access'], $tokens['refresh'], $tokens['expires_at']);
            $request = Http::withToken($tokens['access'])->acceptJson()->asJson()->timeout(10);
            $response = $this->sendWithRetries($request, $method, $url, $json, $query);
        }

        $this->audit($sallaMerchantId, $method, $url, $json, $query, $response, $start);
        return $response;
    }

    /**
     * Perform the HTTP call with transient retries (429/408/5xx and network errors).
     * @param array<string,mixed>|null $json
     * @param array<string,mixed> $query
     */
    private function sendWithRetries(\Illuminate\Http\Client\PendingRequest $request, string $method, string $url, ?array $json, array $query): Response
    {
        $attempts = 0;
        $maxAttempts = 3;
        $lastException = null;
        $response = null;
        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                $response = $request->send($method, $url, [
                    'json' => $json,
                    'query' => $query,
                ]);
                $status = $response->status();
                if (!in_array($status, [429, 408], true) && ($status < 500 || $status >= 600)) {
                    return $response; // success or non-retryable
                }
            } catch (\Throwable $e) {
                $lastException = $e;
            }

            // backoff before retrying
            if ($attempts < $maxAttempts) {
                usleep((int) (100_000 * $attempts)); // 100ms, 200ms
            }
        }

        if ($response instanceof Response) {
            return $response;
        }

        // If we failed with exception and never got a response, synthesize one
        return Http::response([
            'error' => 'upstream_unavailable',
            'message' => $lastException?->getMessage(),
        ], 503);
    }

    /**
     * @return array{access:string,refresh:string,expires_at:\DateTimeInterface}
     */
    private function ensureFresh(string $sallaMerchantId): array
    {
        $record = $this->tokens->get($sallaMerchantId);
        if (!$record) {
            throw new \RuntimeException('No tokens found for merchant '.$sallaMerchantId);
        }
        $expiresAt = $record->access_token_expires_at;
        if ($expiresAt instanceof \DateTimeInterface) {
            if (now()->addSeconds(60) >= $expiresAt) {
                $new = $this->refresher->refresh($sallaMerchantId, (string) $record->refresh_token);
                $this->tokens->updateAccess($sallaMerchantId, $new['access'], $new['refresh'], $new['expires_at']);
                return $new;
            }
        }
        return [
            'access' => (string) $record->access_token,
            'refresh' => (string) $record->refresh_token,
            'expires_at' => $expiresAt ?? now()->addHour(),
        ];
    }

    /**
     * @param array<string,mixed>|null $json
     * @param array<string,mixed> $query
     */
    private function audit(string $sallaMerchantId, string $method, string $url, ?array $json, array $query, Response $response, int $start): void
    {
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

        $body = $response->body();
        $truncatedBody = $body;
        $max = 64 * 1024; // 64KB
        if (strlen($truncatedBody) > $max) {
            $truncatedBody = substr($truncatedBody, 0, $max);
        }

        $resource = $this->inferResourceFromUrl($url);
        $action = $this->inferActionFromMethodAndUrl($method, $url);

        SallaActionAudit::create([
            'merchant_id' => $merchant?->id,
            'salla_merchant_id' => $sallaMerchantId,
            'resource' => $resource,
            'action' => $action,
            'method' => strtoupper($method),
            'endpoint' => $url,
            'request_meta' => [
                'query' => $query,
                'payload' => $json,
            ],
            'status_code' => $response->status(),
            'response_meta' => [
                'body' => $truncatedBody,
                'headers' => $response->headers(),
            ],
            'duration_ms' => $durationMs,
        ]);
    }

    private function inferResourceFromUrl(string $url): string
    {
        foreach (['orders','products','customers','coupons','categories','exports'] as $segment) {
            if (str_contains($url, '/'.$segment)) {
                return $segment;
            }
        }
        return 'unknown';
    }

    private function inferActionFromMethodAndUrl(string $method, string $url): string
    {
        $method = strtoupper($method);
        if ($method === 'GET' && preg_match('#/(\\d+)$#', $url)) return 'get';
        if ($method === 'GET') return 'list';
        if (in_array($method, ['POST','PUT'], true)) return 'create';
        if ($method === 'PATCH') return 'update';
        if ($method === 'DELETE') return 'delete';
        return 'unknown';
    }
}
