<?php

namespace App\Services\Salla;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Carbon\Carbon;

class OAuthTokenStore
{
    /**
     * Store or update tokens for a merchant
     */
    public function put(
        string $sallaMerchantId,
        string $accessToken,
        string $refreshToken,
        Carbon $expiresAt
    ): MerchantToken {
        // Find or create the merchant
        $merchant = Merchant::firstOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            ['is_active' => true]
        );

        // Update or create the token
        return MerchantToken::updateOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'salla_merchant_id' => $sallaMerchantId,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'access_token_expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Get tokens for a merchant by Salla merchant ID
     */
    public function get(string $sallaMerchantId): ?MerchantToken
    {
        return MerchantToken::where('salla_merchant_id', $sallaMerchantId)->first();
    }

    /**
     * Update access token for a merchant
     */
    public function updateAccess(
        string $sallaMerchantId,
        string $accessToken,
        string $refreshToken,
        Carbon $expiresAt
    ): MerchantToken {
        $token = $this->get($sallaMerchantId);
        
        if (!$token) {
            throw new \RuntimeException("No token found for merchant: {$sallaMerchantId}");
        }

        $token->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_token_expires_at' => $expiresAt,
        ]);

        return $token;
    }
}