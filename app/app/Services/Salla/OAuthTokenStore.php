<?php

namespace App\Services\Salla;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Illuminate\Support\Str;

class OAuthTokenStore
{
    public function put(string $sallaMerchantId, string $accessToken, string $refreshToken, \DateTimeInterface $expiresAt): MerchantToken
    {
        $merchant = Merchant::firstOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            [
                'n8n_base_url' => '',
                'is_active' => true,
            ]
        );

        $record = MerchantToken::updateOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'salla_merchant_id' => $sallaMerchantId,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'access_token_expires_at' => $expiresAt,
            ]
        );

        return $record;
    }

    public function get(string $sallaMerchantId): ?MerchantToken
    {
        return MerchantToken::where('salla_merchant_id', $sallaMerchantId)->first();
    }

    public function updateAccess(string $sallaMerchantId, string $accessToken, string $refreshToken, \DateTimeInterface $expiresAt): ?MerchantToken
    {
        $record = $this->get($sallaMerchantId);
        if (!$record) {
            return null;
        }
        $record->fill([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_token_expires_at' => $expiresAt,
        ])->save();

        return $record->refresh();
    }

    public function needsRefresh(MerchantToken $token): bool
    {
        // Return true if token expires within the next 60 seconds
        return $token->access_token_expires_at->isBefore(now()->addSeconds(60));
    }
}
