<?php

namespace App\Services\Salla;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OAuthRefresher
{
    /**
     * Refresh access token using refresh token
     *
     * @param string $sallaMerchantId
     * @param string $refreshToken
     * @return array ['access_token', 'refresh_token', 'expires_at']
     * @throws \Exception
     */
    public function refresh(string $sallaMerchantId, string $refreshToken): array
    {
        $tokenUrl = config('salla_api.oauth.token_url');
        $clientId = config('salla_api.oauth.client_id');
        $clientSecret = config('salla_api.oauth.client_secret');

        if (!$tokenUrl || !$clientId || !$clientSecret) {
            throw new \Exception('Missing OAuth configuration');
        }

        Log::info('Refreshing Salla token', [
            'salla_merchant_id' => $sallaMerchantId,
            'token_url' => $tokenUrl,
        ]);

        try {
            $response = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to refresh Salla token', [
                    'salla_merchant_id' => $sallaMerchantId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to refresh token: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['access_token']) || !isset($data['refresh_token'])) {
                throw new \Exception('Invalid token response format');
            }

            $expiresIn = $data['expires_in'] ?? 3600;
            $expiresAt = Carbon::now()->addSeconds((int) $expiresIn);

            Log::info('Successfully refreshed Salla token', [
                'salla_merchant_id' => $sallaMerchantId,
                'expires_at' => $expiresAt->toISOString(),
            ]);

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => $expiresAt,
            ];

        } catch (\Exception $e) {
            Log::error('Exception refreshing Salla token', [
                'salla_merchant_id' => $sallaMerchantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}