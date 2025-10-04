<?php

namespace App\Services\Salla;

use App\Models\MerchantToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OAuthRefresher
{
    public function __construct(
        private OAuthTokenStore $tokenStore
    ) {}

    public function refresh(MerchantToken $token): ?MerchantToken
    {
        try {
            $response = Http::asForm()->post(config('salla_api.oauth.token_url'), [
                'grant_type' => 'refresh_token',
                'client_id' => config('salla_api.oauth.client_id'),
                'client_secret' => config('salla_api.oauth.client_secret'),
                'refresh_token' => $token->refresh_token,
            ]);

            if (!$response->successful()) {
                Log::error('OAuth refresh failed', [
                    'merchant_id' => $token->salla_merchant_id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            
            return $this->tokenStore->update(
                $token->salla_merchant_id,
                $data['access_token'],
                $data['refresh_token'],
                $data['expires_in']
            );

        } catch (\Exception $e) {
            Log::error('OAuth refresh exception', [
                'merchant_id' => $token->salla_merchant_id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}