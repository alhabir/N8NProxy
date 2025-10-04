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
    ) {}

    /**
     * Make authenticated call to Salla API with automatic token refresh
     *
     * @param string $sallaMerchantId
     * @param string $method HTTP method
     * @param string $templateUrl URL template with placeholders
     * @param array $params URL parameters for template expansion
     * @param array|null $json JSON payload
     * @param array $query Query parameters
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
        $url = Endpoint::expand($templateUrl, array_merge(['base' => config('salla_api.base')], $params));
        $token = $this->ensureFresh($sallaMerchantId);

        $start = hrtime(true);
        
        // Build the HTTP request
        $request = Http::withToken($token['access_token'])
            ->acceptJson()
            ->timeout(10);

        if ($json !== null) {
            $request = $request->asJson();
        }

        // Make the initial request
        $response = $request->send($method, $url, [
            'json' => $json,
            'query' => $query,
        ]);

        // If we get 401 (unauthorized), try refreshing the token and retry once
        if ($response->status() === 401) {
            Log::info('Token expired, attempting refresh', [
                'salla_merchant_id' => $sallaMerchantId,
                'url' => $url,
            ]);

            $refreshedToken = $this->refresher->refresh($sallaMerchantId, $token['refresh_token']);
            $this->tokens->updateAccess(
                $sallaMerchantId,
                $refreshedToken['access_token'],
                $refreshedToken['refresh_token'],
                $refreshedToken['expires_at']
            );

            // Retry with the new token
            $request = Http::withToken($refreshedToken['access_token'])
                ->acceptJson()
                ->timeout(10);

            if ($json !== null) {
                $request = $request->asJson();
            }

            $response = $request->send($method, $url, [
                'json' => $json,
                'query' => $query,
            ]);
        }

        // Audit the request
        $this->audit($sallaMerchantId, $method, $url, $json, $query, $response, $start);

        return $response;
    }

    /**
     * Ensure the token is fresh (not expiring within 60 seconds)
     */
    private function ensureFresh(string $sallaMerchantId): array
    {
        $tokenRecord = $this->tokens->get($sallaMerchantId);

        if (!$tokenRecord) {
            throw new \Exception("No token found for merchant: {$sallaMerchantId}");
        }

        // If token is expiring soon, refresh it proactively
        if ($tokenRecord->isTokenExpiring(60)) {
            Log::info('Token expiring soon, proactively refreshing', [
                'salla_merchant_id' => $sallaMerchantId,
                'expires_at' => $tokenRecord->access_token_expires_at?->toISOString(),
            ]);

            $refreshedToken = $this->refresher->refresh($sallaMerchantId, $tokenRecord->refresh_token);
            $this->tokens->updateAccess(
                $sallaMerchantId,
                $refreshedToken['access_token'],
                $refreshedToken['refresh_token'],
                $refreshedToken['expires_at']
            );

            return $refreshedToken;
        }

        return [
            'access_token' => $tokenRecord->access_token,
            'refresh_token' => $tokenRecord->refresh_token,
        ];
    }

    /**
     * Audit the API call
     */
    private function audit(
        string $sallaMerchantId,
        string $method,
        string $url,
        ?array $json,
        array $query,
        Response $response,
        int $startTime
    ): void {
        $duration = (int) ((hrtime(true) - $startTime) / 1_000_000); // Convert to milliseconds

        // Parse resource and action from URL
        $resource = 'unknown';
        $action = 'unknown';

        // Extract resource from URL pattern (e.g., /admin/v2/orders -> orders)
        if (preg_match('/\/admin\/v\d+\/(\w+)/', $url, $matches)) {
            $resource = $matches[1];
        }

        // Determine action from method and URL patterns
        $action = match ($method) {
            'GET' => str_contains($url, '/{id}') ? 'get' : 'list',
            'POST' => 'create',
            'PATCH', 'PUT' => 'update',
            'DELETE' => 'delete',
            default => strtolower($method),
        };

        // Handle special cases like exports
        if (str_contains($url, '/download')) {
            $action = 'download';
        } elseif (str_contains($url, '/status')) {
            $action = 'status';
        }

        // Sanitize request data (limit size and remove sensitive info)
        $requestMeta = [
            'query' => $query,
            'payload_size' => $json ? strlen(json_encode($json)) : 0,
        ];

        // Only store truncated payload for audit (not full payload for security)
        if ($json && count($json) < 10) {
            $requestMeta['payload_keys'] = array_keys($json);
        }

        // Sanitize response data (truncate large responses)
        $responseBody = $response->body();
        $responseMeta = [
            'size' => strlen($responseBody),
            'truncated' => false,
        ];

        // Truncate large response bodies
        if (strlen($responseBody) > 64000) {
            $responseBody = substr($responseBody, 0, 64000) . '...';
            $responseMeta['truncated'] = true;
        }

        if ($response->successful() && $responseBody) {
            $responseJson = $response->json();
            if (is_array($responseJson)) {
                $responseMeta['keys'] = array_keys($responseJson);
                if (isset($responseJson['data']) && is_array($responseJson['data'])) {
                    $responseMeta['data_count'] = count($responseJson['data']);
                }
            }
        }

        // Find the merchant for proper FK
        $merchant = \App\Models\Merchant::where('salla_merchant_id', $sallaMerchantId)->first();

        try {
            SallaActionAudit::create([
                'merchant_id' => $merchant?->id,
                'salla_merchant_id' => $sallaMerchantId,
                'resource' => $resource,
                'action' => $action,
                'method' => $method,
                'endpoint' => $url,
                'request_meta' => $requestMeta,
                'status_code' => $response->status(),
                'response_meta' => $responseMeta,
                'duration_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            // Log audit failure but don't break the main flow
            Log::error('Failed to audit Salla API call', [
                'error' => $e->getMessage(),
                'salla_merchant_id' => $sallaMerchantId,
                'endpoint' => $url,
            ]);
        }
    }
}