<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\User;
use App\Services\Salla\OAuthTokenStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SallaAppEventsController extends Controller
{
    public function __construct(
        private OAuthTokenStore $tokenStore
    ) {}

    public function authorized(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        $sallaMerchantId = data_get($payload, 'data.store.id');
        $storeName = data_get($payload, 'data.store.name');
        $email = Str::lower(data_get($payload, 'data.store.email') ?? sprintf('merchant-%s@n8ndesigner.local', $sallaMerchantId));
        $accessToken = data_get($payload, 'data.tokens.access_token');
        $refreshToken = data_get($payload, 'data.tokens.refresh_token');
        $expiresIn = (int) data_get($payload, 'data.tokens.expires_in', 3600);

        if (!$sallaMerchantId || !$accessToken || !$refreshToken) {
            Log::warning('Missing required data in app.store.authorize event', [
                'merchant_id' => $sallaMerchantId,
                'has_access_token' => (bool) $accessToken,
                'has_refresh_token' => (bool) $refreshToken,
            ]);

            return response()->json(['error' => 'Missing required data'], 422);
        }

        try {
            DB::transaction(function () use ($sallaMerchantId, $storeName, $email, $accessToken, $refreshToken, $expiresIn) {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $storeName ?? 'Salla Merchant',
                        'password' => Hash::make(Str::password(16)),
                        'is_admin' => false,
                    ]
                );

                $this->upsertMerchant($user, $sallaMerchantId, $storeName, $email);

                $this->tokenStore->put($sallaMerchantId, $accessToken, $refreshToken, $expiresIn);
            });
        } catch (Throwable $e) {
            Log::error('Failed to process app.store.authorize event', [
                'merchant_id' => $sallaMerchantId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }

        Log::info('App authorized for merchant', [
            'merchant_id' => $sallaMerchantId,
            'store_name' => $storeName,
        ]);

        return response()->json(['ok' => true]);
    }

    public function installed(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        $sallaMerchantId = data_get($payload, 'data.store.id');
        $storeName = data_get($payload, 'data.store.name');
        $email = Str::lower(data_get($payload, 'data.store.email') ?? sprintf('merchant-%s@n8ndesigner.local', $sallaMerchantId));

        if (!$sallaMerchantId) {
            Log::warning('Missing store ID in app.installed event', $payload);
            return response()->json(['error' => 'Missing store ID'], 422);
        }

        try {
            DB::transaction(function () use ($sallaMerchantId, $storeName, $email) {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $storeName ?? 'Salla Merchant',
                        'password' => Hash::make(Str::password(16)),
                        'is_admin' => false,
                    ]
                );

                $this->upsertMerchant($user, $sallaMerchantId, $storeName, $email);
            });
        } catch (Throwable $e) {
            Log::error('Failed to process app.installed event', [
                'merchant_id' => $sallaMerchantId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }

        Log::info('App installed for merchant', [
            'merchant_id' => $sallaMerchantId,
            'store_name' => $storeName,
        ]);

        return response()->json(['ok' => true]);
    }

    private function upsertMerchant(User $user, string $sallaMerchantId, ?string $storeName, string $email): Merchant
    {
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();

        if (!$merchant) {
            $merchant = Merchant::where('user_id', $user->id)
                ->orWhere('email', $email)
                ->first();
        }

        if (!$merchant) {
            $merchant = new Merchant([
                'salla_merchant_id' => $sallaMerchantId,
            ]);
            $merchant->is_active = true;
            $merchant->is_approved = false;
        }

        $merchant->user_id = $user->id;
        $merchant->salla_merchant_id = $sallaMerchantId;
        $merchant->email = $email;

        if ($storeName) {
            $merchant->store_name = $storeName;
        }

        if (!$merchant->store_id) {
            $merchant->store_id = sprintf('salla-%s', $sallaMerchantId);
        }

        $merchant->save();

        return $merchant;
    }
}
