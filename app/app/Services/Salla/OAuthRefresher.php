<?php

namespace App\Services\Salla;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OAuthRefresher
{
    public function __construct(
        private OAuthTokenStore $tokenStore
    ) {}

    /**
     * Refresh OAuth tokens using refresh token
     *
     * @param string $sallaMerchantId
     * @param string $refreshToken
     * @return array{access: string, refresh: string, expires_at: Carbon}
     * @throws \Exception
     */
    public function refresh(string $sallaMerchantId, string $refreshToken): array
    {
        $response = Http::asForm()
            ->timeout(10)
            ->post(config('salla_api.oauth.token_url'), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('salla_api.oauth.client_id'),
                'client_secret' => config('salla_api.oauth.client_secret'),
            ]);

        if (!$response->successful()) {
            Log::error('OAuth refresh failed', [
                'merchant_id' => $sallaMerchantId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            throw new \Exception('Failed to refresh OAuth token: ' . $response->body());
        }

        $data = $response->json();
        
        $accessToken = $data['access_token'] ?? null;
        $newRefreshToken = $data['refresh_token'] ?? $refreshToken; // Some providers return new refresh token
        $expiresIn = $data['expires_in'] ?? 3600;
        
        if (!$accessToken) {
            throw new \Exception('No access token in refresh response');
        }

        $expiresAt = now()->addSeconds($expiresIn);

        // Update stored tokens
        $this->tokenStore->put($sallaMerchantId, $accessToken, $newRefreshToken, $expiresAt);

        return [
            'access' => $accessToken,
            'refresh' => $newRefreshToken,
            'expires_at' => $expiresAt,
        ];
    }
}