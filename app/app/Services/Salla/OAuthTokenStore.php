<?php

namespace App\Services\Salla;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Carbon\Carbon;

class OAuthTokenStore
{
    /**
     * Store or update OAuth tokens for a merchant
     *
     * @param string $sallaMerchantId
     * @param string $accessToken
     * @param string $refreshToken
     * @param Carbon $expiresAt
     * @return MerchantToken
     */
    public function put(string $sallaMerchantId, string $accessToken, string $refreshToken, Carbon $expiresAt): MerchantToken
    {
        // Find or create merchant
        $merchant = Merchant::firstOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            [
                'store_name' => 'Store ' . $sallaMerchantId,
                'is_active' => true,
            ]
        );

        // Store or update token
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
     * Get token for a merchant
     *
     * @param string $sallaMerchantId
     * @return MerchantToken|null
     */
    public function get(string $sallaMerchantId): ?MerchantToken
    {
        return MerchantToken::where('salla_merchant_id', $sallaMerchantId)->first();
    }

    /**
     * Update only the access token (after refresh)
     *
     * @param string $sallaMerchantId
     * @param string $accessToken
     * @param Carbon $expiresAt
     * @return MerchantToken|null
     */
    public function updateAccess(string $sallaMerchantId, string $accessToken, Carbon $expiresAt): ?MerchantToken
    {
        $token = $this->get($sallaMerchantId);
        
        if (!$token) {
            return null;
        }

        $token->update([
            'access_token' => $accessToken,
            'access_token_expires_at' => $expiresAt,
        ]);

        return $token;
    }

    /**
     * Check if token needs refresh (expires in less than 60 seconds)
     *
     * @param MerchantToken $token
     * @return bool
     */
    public function needsRefresh(MerchantToken $token): bool
    {
        if (!$token->access_token_expires_at) {
            return true;
        }

        return $token->access_token_expires_at->subSeconds(60)->isPast();
    }
}