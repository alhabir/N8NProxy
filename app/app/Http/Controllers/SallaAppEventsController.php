<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\Salla\OAuthTokenStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SallaAppEventsController extends Controller
{
    public function __construct(private OAuthTokenStore $store) {}

    /**
     * Handle app.store.authorize event - capture OAuth tokens
     */
    public function authorized(Request $request)
    {
        try {
            $payload = $request->json()->all();

            $sallaMerchantId = data_get($payload, 'data.store.id');
            $accessToken = data_get($payload, 'data.tokens.access_token');
            $refreshToken = data_get($payload, 'data.tokens.refresh_token');
            $expiresIn = (int) data_get($payload, 'data.tokens.expires_in', 3600);

            if (!$sallaMerchantId || !$accessToken || !$refreshToken) {
                Log::warning('Invalid app authorization payload', [
                    'payload' => $payload,
                ]);
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            $expiresAt = now()->addSeconds($expiresIn);

            // Ensure merchant exists and is active
            $merchant = Merchant::firstOrCreate(
                ['salla_merchant_id' => $sallaMerchantId],
                ['is_active' => true]
            );

            // Store the tokens
            $this->store->put($sallaMerchantId, $accessToken, $refreshToken, $expiresAt);

            Log::info('Salla app authorized', [
                'salla_merchant_id' => $sallaMerchantId,
                'merchant_id' => $merchant->id,
                'expires_at' => $expiresAt->toISOString(),
            ]);

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to handle app authorization', [
                'error' => $e->getMessage(),
                'payload' => $request->json()->all(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle app.store.installed event - optional bookkeeping
     */
    public function installed(Request $request)
    {
        try {
            $payload = $request->json()->all();
            $sallaMerchantId = data_get($payload, 'data.store.id');

            if ($sallaMerchantId) {
                // Ensure merchant placeholder exists
                Merchant::firstOrCreate(
                    ['salla_merchant_id' => $sallaMerchantId],
                    ['is_active' => true]
                );

                Log::info('Salla app installed', [
                    'salla_merchant_id' => $sallaMerchantId,
                ]);
            }

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to handle app installation', [
                'error' => $e->getMessage(),
                'payload' => $request->json()->all(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}