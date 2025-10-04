<?php

namespace App\Services\Salla;

use Illuminate\Support\Facades\Http;

class OAuthRefresher
{
    public function __construct(private OAuthTokenStore $tokenStore) {}

    public function refresh(string $sallaMerchantId, string $refreshToken): array
    {
        $res = Http::asForm()->post((string) config('salla_api.oauth.token_url'), [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => (string) config('salla_api.oauth.client_id'),
            'client_secret' => (string) config('salla_api.oauth.client_secret'),
        ]);

        if (!$res->ok()) {
            throw new \Exception('Failed to refresh OAuth token: '.$res->status());
        }

        $body = $res->json();
        $accessToken = (string) ($body['access_token'] ?? '');
        $newRefreshToken = (string) ($body['refresh_token'] ?? $refreshToken);
        $expiresAt = now()->addSeconds((int) ($body['expires_in'] ?? 3600));

        // Update tokens in storage
        $this->tokenStore->updateAccess($sallaMerchantId, $accessToken, $newRefreshToken, $expiresAt);

        return [
            'access' => $accessToken,
            'refresh' => $newRefreshToken,
            'expires_at' => $expiresAt,
        ];
    }
}
