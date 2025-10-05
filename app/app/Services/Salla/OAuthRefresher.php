<?php

namespace App\Services\Salla;

use App\Models\MerchantToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OAuthRefresher
{
    public function __construct(
        private OAuthTokenStore $tokenStore
    ) {}

    /**
     * Refresh the OAuth token for a merchant.
     *
     * @throws \Exception when the refresh fails.
     */
    public function refresh(string $sallaMerchantId, string $refreshToken): array
    {
        try {
            $response = Http::asForm()->post(config('salla_api.oauth.token_url'), [
                'grant_type' => 'refresh_token',
                'client_id' => config('salla_api.oauth.client_id'),
                'client_secret' => config('salla_api.oauth.client_secret'),
                'refresh_token' => $refreshToken,
            ]);

            if (!$response->successful()) {
                Log::error('OAuth refresh failed', [
                    'merchant_id' => $sallaMerchantId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception('Failed to refresh OAuth token');
            }

            $data = $response->json();

            if (!isset($data['access_token'], $data['refresh_token'], $data['expires_in'])) {
                Log::error('OAuth refresh missing data', [
                    'merchant_id' => $sallaMerchantId,
                    'response' => $data,
                ]);

                throw new \Exception('Failed to refresh OAuth token');
            }

            $updatedToken = $this->tokenStore->store(
                $sallaMerchantId,
                $data['access_token'],
                $data['refresh_token'],
                (int) $data['expires_in']
            );

            return [
                'access' => $updatedToken->access_token,
                'refresh' => $updatedToken->refresh_token,
                'expires_at' => $updatedToken->access_token_expires_at,
                'token' => $updatedToken,
            ];

        } catch (Throwable $e) {
            Log::error('OAuth refresh exception', [
                'merchant_id' => $sallaMerchantId,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof \Exception) {
                throw $e;
            }

            throw new \Exception('Failed to refresh OAuth token', previous: $e);
        }
    }

    public function refreshUsingToken(MerchantToken $token): MerchantToken
    {
        $result = $this->refresh($token->salla_merchant_id, $token->refresh_token);

        return $result['token'];
    }
}