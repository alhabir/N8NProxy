<?php

namespace App\Services\Salla;

use Illuminate\Support\Facades\Http;

class OAuthRefresher
{
    public function refresh(string $sallaMerchantId, string $refreshToken): array
    {
        $res = Http::asForm()->post((string) config('salla_api.oauth.token_url'), [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => (string) config('salla_api.oauth.client_id'),
            'client_secret' => (string) config('salla_api.oauth.client_secret'),
        ]);

        if (!$res->ok()) {
            throw new \RuntimeException('Failed to refresh token: '.$res->status());
        }

        $body = $res->json();
        return [
            'access' => (string) ($body['access_token'] ?? ''),
            'refresh' => (string) ($body['refresh_token'] ?? $refreshToken),
            'expires_at' => now()->addSeconds((int) ($body['expires_in'] ?? 3600)),
        ];
    }
}
