<?php

namespace App\Services\Salla;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Carbon\Carbon;

class OAuthTokenStore
{
    public function store(string $sallaMerchantId, string $accessToken, string $refreshToken, int $expiresIn): MerchantToken
    {
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        
        if (!$merchant) {
            throw new \Exception("Merchant not found for Salla ID: {$sallaMerchantId}");
        }

        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        return MerchantToken::updateOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            [
                'merchant_id' => $merchant->id,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'access_token_expires_at' => $expiresAt,
            ]
        );
    }

    public function get(string $sallaMerchantId): ?MerchantToken
    {
        return MerchantToken::where('salla_merchant_id', $sallaMerchantId)->first();
    }

    public function getValidToken(string $sallaMerchantId): ?MerchantToken
    {
        $token = $this->get($sallaMerchantId);
        
        if (!$token || $token->isExpired()) {
            return null;
        }

        return $token;
    }

    public function update(string $sallaMerchantId, string $accessToken, string $refreshToken, int $expiresIn): MerchantToken
    {
        $token = $this->get($sallaMerchantId);
        
        if (!$token) {
            throw new \Exception("Token not found for Salla ID: {$sallaMerchantId}");
        }

        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        $token->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_token_expires_at' => $expiresAt,
        ]);

        return $token;
    }
}