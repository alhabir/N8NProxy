<?php

namespace App\Services\Salla;

use App\Models\SallaActionAudit;
use App\Support\Endpoint;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SallaHttpClient
{
    public function __construct(
        private OAuthTokenStore $tokens,
        private OAuthRefresher $refresher
    ) {
    }

    /**
     * Make authenticated API call to Salla
     *
     * @param string $sallaMerchantId Salla merchant/store ID
     * @param string $method HTTP method (GET, POST, PATCH, DELETE)
     * @param string $templateUrl URL template with {placeholders}
     * @param array $params URL parameters to expand
     * @param array|null $json JSON payload (for POST/PATCH)
     * @param array $query Query string parameters
     * @return Response
     * @throws \Exception
     */
    public function call(
        string $sallaMerchantId,
        string $method,
        string $templateUrl,
        array $params = [],
        ?array $json = null,
        array $query = []
    ): Response {
        // Expand URL template
        $url = Endpoint::expand($templateUrl, array_merge(['base' => config('salla_api.base')], $params));

        // Extract resource and action from template for audit
        $resource = $this->extractResource($templateUrl);
        $action = $this->extractAction($method, $templateUrl);

        // Ensure token is fresh
        $token = $this->ensureFresh($sallaMerchantId);

        // Build request
        $request = Http::withToken($token['access'])
            ->acceptJson()
            ->asJson()
            ->timeout(10);

        $start = hrtime(true);

        // Send request
        $response = $request->send($method, $url, [
            'json' => $json,
            'query' => $query,
        ]);

        // If 401, refresh token and retry once
        if ($response->status() === 401) {
            Log::info('Received 401, refreshing token', ['merchant' => $sallaMerchantId]);

            $token = $this->refresher->refresh($sallaMerchantId, $token['refresh']);

            // Retry with new token
            $request = Http::withToken($token['access'])
                ->acceptJson()
                ->asJson()
                ->timeout(10);

            $start = hrtime(true);

            $response = $request->send($method, $url, [
                'json' => $json,
                'query' => $query,
            ]);
        }

        // Calculate duration and audit
        $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);

        $this->audit(
            $sallaMerchantId,
            $resource,
            $action,
            $method,
            $url,
            $json,
            $query,
            $response,
            $durationMs
        );

        return $response;
    }

    /**
     * Ensure token is fresh (not expiring in next 60 seconds)
     *
     * @param string $sallaMerchantId
     * @return array ['access' => string, 'refresh' => string]
     * @throws \Exception
     */
    private function ensureFresh(string $sallaMerchantId): array
    {
        $tokenRecord = $this->tokens->get($sallaMerchantId);

        if (!$tokenRecord) {
            throw new \Exception("No tokens found for merchant: {$sallaMerchantId}");
        }

        // Check if token is expiring soon (within 60 seconds)
        $expiresAt = $tokenRecord->access_token_expires_at;
        if ($expiresAt && $expiresAt->isBefore(now()->addSeconds(60))) {
            Log::info('Token expiring soon, refreshing', ['merchant' => $sallaMerchantId]);
            return $this->refresher->refresh($sallaMerchantId, $tokenRecord->refresh_token);
        }

        return [
            'access' => $tokenRecord->access_token,
            'refresh' => $tokenRecord->refresh_token,
        ];
    }

    /**
     * Audit API call
     */
    private function audit(
        string $sallaMerchantId,
        string $resource,
        string $action,
        string $method,
        string $url,
        ?array $json,
        array $query,
        Response $response,
        int $durationMs
    ): void {
        try {
            $merchantToken = $this->tokens->get($sallaMerchantId);

            SallaActionAudit::create([
                'merchant_id' => $merchantToken?->merchant_id,
                'salla_merchant_id' => $sallaMerchantId,
                'resource' => $resource,
                'action' => $action,
                'method' => $method,
                'endpoint' => $url,
                'request_meta' => [
                    'query' => $query,
                    'payload' => $this->sanitize($json),
                ],
                'status_code' => $response->status(),
                'response_meta' => [
                    'body' => $this->truncate($response->body(), 65536),
                    'headers' => $response->headers(),
                ],
                'duration_ms' => $durationMs,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to audit API call', [
                'error' => $e->getMessage(),
                'merchant' => $sallaMerchantId,
            ]);
        }
    }

    /**
     * Extract resource name from URL template
     */
    private function extractResource(string $template): string
    {
        // Extract first path segment after {base}
        if (preg_match('#\{base\}/([^/]+)#', $template, $matches)) {
            return $matches[1];
        }

        return 'unknown';
    }

    /**
     * Extract action from method and template
     */
    private function extractAction(string $method, string $template): string
    {
        $method = strtolower($method);

        if ($method === 'get' && str_contains($template, '{id}')) {
            return 'get';
        } elseif ($method === 'get') {
            return 'list';
        } elseif ($method === 'post') {
            return 'create';
        } elseif ($method === 'patch' || $method === 'put') {
            return 'update';
        } elseif ($method === 'delete') {
            return 'delete';
        }

        return $method;
    }

    /**
     * Sanitize sensitive data from payload
     */
    private function sanitize(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        // Remove sensitive keys
        $sensitiveKeys = ['password', 'token', 'secret', 'api_key'];

        return array_map(function ($value) use ($sensitiveKeys) {
            if (is_array($value)) {
                return $this->sanitize($value);
            }
            return $value;
        }, $data);
    }

    /**
     * Truncate string to max length
     */
    private function truncate(?string $str, int $maxLength): ?string
    {
        if (!$str || strlen($str) <= $maxLength) {
            return $str;
        }

        return substr($str, 0, $maxLength) . '... [truncated]';
    }
}
