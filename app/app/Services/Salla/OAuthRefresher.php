<?php

namespace App\Services\Salla;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OAuthRefresher
{
    public function __construct(
        private OAuthTokenStore $tokenStore
    ) {
    }

    /**
     * Refresh OAuth token
     *
     * @param string $sallaMerchantId Salla merchant/store ID
     * @param string $refreshToken Current refresh token
     * @return array ['access' => string, 'refresh' => string, 'expires_at' => Carbon]
     * @throws \Exception
     */
    public function refresh(string $sallaMerchantId, string $refreshToken): array
    {
        $tokenUrl = config('salla_api.oauth.token_url');
        $clientId = config('salla_api.oauth.client_id');
        $clientSecret = config('salla_api.oauth.client_secret');

        Log::info('Refreshing OAuth token', ['merchant' => $sallaMerchantId]);

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        if (!$response->successful()) {
            Log::error('OAuth token refresh failed', [
                'merchant' => $sallaMerchantId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to refresh OAuth token: ' . $response->body());
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        $newRefreshToken = $data['refresh_token'] ?? $refreshToken;
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $expiresAt = now()->addSeconds($expiresIn);

        if (!$accessToken) {
            throw new \Exception('No access token in refresh response');
        }

        // Update stored tokens
        $this->tokenStore->updateAccess($sallaMerchantId, $accessToken, $newRefreshToken, $expiresAt);

        Log::info('OAuth token refreshed successfully', ['merchant' => $sallaMerchantId]);

        return [
            'access' => $accessToken,
            'refresh' => $newRefreshToken,
            'expires_at' => $expiresAt,
        ];
    }
}
