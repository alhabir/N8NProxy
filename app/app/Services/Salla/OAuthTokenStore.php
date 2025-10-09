<?php

namespace App\Services\Salla;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class OAuthTokenStore
{
    public function store(string $sallaMerchantId, string $accessToken, string $refreshToken, int $expiresIn): MerchantToken
    {
        return $this->persist(
            $sallaMerchantId,
            $accessToken,
            $refreshToken,
            Carbon::now()->addSeconds($expiresIn)
        );
    }

    public function put(string $sallaMerchantId, string $accessToken, string $refreshToken, CarbonInterface|string|int $expiresAt): MerchantToken
    {
        return $this->persist(
            $sallaMerchantId,
            $accessToken,
            $refreshToken,
            $this->normalizeExpiration($expiresAt)
        );
    }

    public function get(string $sallaMerchantId): ?MerchantToken
    {
        return MerchantToken::where('salla_merchant_id', $sallaMerchantId)->first();
    }

    public function getValidToken(string $sallaMerchantId): ?MerchantToken
    {
        $token = $this->get($sallaMerchantId);

        if (!$token || $this->needsRefresh($token)) {
            return null;
        }

        return $token;
    }

    public function update(string $sallaMerchantId, string $accessToken, string $refreshToken, int $expiresIn): MerchantToken
    {
        return $this->persist(
            $sallaMerchantId,
            $accessToken,
            $refreshToken,
            Carbon::now()->addSeconds($expiresIn)
        );
    }

    public function needsRefresh(MerchantToken $token, int $thresholdSeconds = 60): bool
    {
        if (!$token->access_token_expires_at) {
            return true;
        }

        return $token->access_token_expires_at->lte(now()->addSeconds($thresholdSeconds));
    }

    private function persist(string $sallaMerchantId, string $accessToken, string $refreshToken, CarbonInterface $expiresAt): MerchantToken
    {
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();

        if (!$merchant) {
            throw new \Exception("Merchant not found for Salla ID: {$sallaMerchantId}");
        }

        $merchant->forceFill([
            'salla_access_token' => $accessToken,
            'salla_refresh_token' => $refreshToken,
            'salla_token_expires_at' => $expiresAt,
            'connected_at' => $merchant->connected_at ?? now(),
        ])->save();

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

    private function normalizeExpiration(CarbonInterface|string|int $expiresAt): CarbonInterface
    {
        if ($expiresAt instanceof CarbonInterface) {
            return $expiresAt;
        }

        if (is_numeric($expiresAt)) {
            return Carbon::now()->addSeconds((int) $expiresAt);
        }

        return Carbon::parse($expiresAt);
    }
}
