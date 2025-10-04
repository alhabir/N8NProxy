<?php

namespace App\Services\Salla;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Carbon\Carbon;

class OAuthTokenStore
{
    /**
     * Store or update tokens for a merchant
     *
     * @param string $sallaMerchantId Salla merchant/store ID
     * @param string $accessToken Access token
     * @param string $refreshToken Refresh token
     * @param Carbon|null $expiresAt Token expiration timestamp
     * @return MerchantToken
     */
    public function put(
        string $sallaMerchantId,
        string $accessToken,
        string $refreshToken,
        ?Carbon $expiresAt = null
    ): MerchantToken {
        // Find or create merchant
        $merchant = Merchant::firstOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            ['is_active' => true]
        );

        // Update or create token record
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
     * Get token record for a merchant
     *
     * @param string $sallaMerchantId Salla merchant/store ID
     * @return MerchantToken|null
     */
    public function get(string $sallaMerchantId): ?MerchantToken
    {
        return MerchantToken::where('salla_merchant_id', $sallaMerchantId)->first();
    }

    /**
     * Update access token after refresh
     *
     * @param string $sallaMerchantId Salla merchant/store ID
     * @param string $accessToken New access token
     * @param string $refreshToken New refresh token
     * @param Carbon|null $expiresAt New expiration
     * @return bool
     */
    public function updateAccess(
        string $sallaMerchantId,
        string $accessToken,
        string $refreshToken,
        ?Carbon $expiresAt = null
    ): bool {
        $token = $this->get($sallaMerchantId);

        if (!$token) {
            return false;
        }

        return $token->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_token_expires_at' => $expiresAt,
        ]);
    }
}
