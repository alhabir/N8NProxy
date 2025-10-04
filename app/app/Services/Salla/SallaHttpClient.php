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
        private OAuthTokenStore $tokenStore,
        private OAuthRefresher $refresher
    ) {}

    /**
     * Make an API call to Salla
     *
     * @param string $sallaMerchantId
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $templateUrl URL template with placeholders
     * @param array $params URL parameters to replace in template
     * @param array|null $json JSON payload for POST/PUT/PATCH
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
        
        // Get fresh token
        $token = $this->ensureFresh($sallaMerchantId);
        if (!$token) {
            throw new \Exception("No OAuth token found for merchant: $sallaMerchantId");
        }

        // Prepare request
        $request = Http::withToken($token->access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(10);

        // Track timing
        $startTime = hrtime(true);
        
        // Make request
        $response = $this->sendRequest($request, $method, $url, $json, $query);
        
        // Handle 401 (token expired) with retry
        if ($response->status() === 401) {
            Log::info('Token expired, refreshing', ['merchant' => $sallaMerchantId]);
            
            try {
                $refreshed = $this->refresher->refresh($sallaMerchantId, $token->refresh_token);
                
                // Retry with fresh token
                $request = Http::withToken($refreshed['access'])
                    ->acceptJson()
                    ->asJson()
                    ->timeout(10);
                    
                $response = $this->sendRequest($request, $method, $url, $json, $query);
            } catch (\Exception $e) {
                Log::error('Token refresh failed', [
                    'merchant' => $sallaMerchantId,
                    'error' => $e->getMessage(),
                ]);
                // Continue with 401 response for auditing
            }
        }

        // Audit the request
        $this->audit($sallaMerchantId, $method, $url, $json, $query, $response, $startTime);
        
        return $response;
    }

    /**
     * Ensure token is fresh (not expiring soon)
     *
     * @param string $sallaMerchantId
     * @return \App\Models\MerchantToken|null
     */
    private function ensureFresh(string $sallaMerchantId): ?\App\Models\MerchantToken
    {
        $token = $this->tokenStore->get($sallaMerchantId);
        
        if (!$token) {
            return null;
        }

        // Refresh if expiring in less than 60 seconds
        if ($this->tokenStore->needsRefresh($token)) {
            try {
                Log::info('Token expiring soon, refreshing proactively', ['merchant' => $sallaMerchantId]);
                $this->refresher->refresh($sallaMerchantId, $token->refresh_token);
                // Re-fetch updated token
                $token = $this->tokenStore->get($sallaMerchantId);
            } catch (\Exception $e) {
                Log::error('Proactive token refresh failed', [
                    'merchant' => $sallaMerchantId,
                    'error' => $e->getMessage(),
                ]);
                // Return existing token, will handle 401 if it's actually expired
            }
        }

        return $token;
    }

    /**
     * Send the actual HTTP request
     *
     * @param \Illuminate\Http\Client\PendingRequest $request
     * @param string $method
     * @param string $url
     * @param array|null $json
     * @param array $query
     * @return Response
     */
    private function sendRequest($request, string $method, string $url, ?array $json, array $query): Response
    {
        $options = [];
        
        if (!empty($json)) {
            $options['json'] = $json;
        }
        
        if (!empty($query)) {
            $options['query'] = $query;
        }

        return $request->send($method, $url, $options);
    }

    /**
     * Audit the API call
     *
     * @param string $sallaMerchantId
     * @param string $method
     * @param string $url
     * @param array|null $json
     * @param array $query
     * @param Response $response
     * @param int $startTime
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
        $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);
        
        // Extract resource and action from URL
        $urlParts = parse_url($url);
        $pathParts = explode('/', trim($urlParts['path'] ?? '', '/'));
        $resource = $pathParts[count($pathParts) - 2] ?? 'unknown';
        $action = $this->inferAction($method, $pathParts);
        
        // Prepare request meta (sanitized)
        $requestMeta = [
            'query' => $this->sanitizeForAudit($query),
            'payload' => $json ? $this->sanitizeForAudit($json) : null,
        ];
        
        // Prepare response meta (truncated)
        $responseBody = $response->json() ?? $response->body();
        $responseMeta = [
            'body' => $this->truncateForAudit($responseBody),
            'headers' => $response->headers(),
        ];

        // Find merchant by Salla ID
        $merchant = \App\Models\Merchant::where('salla_merchant_id', $sallaMerchantId)->first();

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
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Infer action from method and URL
     *
     * @param string $method
     * @param array $pathParts
     * @return string
     */
    private function inferAction(string $method, array $pathParts): string
    {
        $lastPart = end($pathParts);
        
        // Check for specific endpoints
        if ($lastPart === 'download') {
            return 'download';
        }
        
        // Check if last part is likely an ID (numeric or UUID-like)
        $hasId = preg_match('/^[0-9a-f\-]+$/i', $lastPart) && strlen($lastPart) > 8;
        
        return match ($method) {
            'GET' => $hasId ? 'get' : 'list',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => strtolower($method),
        };
    }

    /**
     * Sanitize data for audit (remove sensitive fields)
     *
     * @param array $data
     * @return array
     */
    private function sanitizeForAudit(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'key', 'credit_card'];
        
        foreach ($data as $key => $value) {
            foreach ($sensitive as $term) {
                if (stripos($key, $term) !== false) {
                    $data[$key] = '[REDACTED]';
                    break;
                }
            }
            
            if (is_array($value)) {
                $data[$key] = $this->sanitizeForAudit($value);
            }
        }
        
        return $data;
    }

    /**
     * Truncate data for audit storage (max 64KB)
     *
     * @param mixed $data
     * @return mixed
     */
    private function truncateForAudit($data)
    {
        $json = json_encode($data);
        
        if (strlen($json) > 65536) { // 64KB
            if (is_array($data)) {
                return [
                    '_truncated' => true,
                    '_original_size' => strlen($json),
                    '_sample' => array_slice($data, 0, 10),
                ];
            }
            
            return substr($json, 0, 65536) . '... [TRUNCATED]';
        }
        
        return $data;
    }
}