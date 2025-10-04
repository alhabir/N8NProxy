<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\Salla\OAuthTokenStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SallaAppEventsController extends Controller
{
    public function __construct(
        private OAuthTokenStore $store
    ) {
    }

    /**
     * Handle app.store.authorize event
     * This is the primary way we capture OAuth tokens from Salla
     */
    public function authorized(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        Log::info('Received app.store.authorize event', ['payload' => $payload]);

        $sallaMerchantId = data_get($payload, 'data.store.id');
        $accessToken = data_get($payload, 'data.tokens.access_token');
        $refreshToken = data_get($payload, 'data.tokens.refresh_token');
        $expiresIn = (int) data_get($payload, 'data.tokens.expires_in', 3600);

        if (!$sallaMerchantId || !$accessToken || !$refreshToken) {
            Log::error('Missing required fields in authorize event', ['payload' => $payload]);
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        $expiresAt = now()->addSeconds($expiresIn);

        // Ensure merchant exists and is active
        $merchant = Merchant::firstOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            ['is_active' => true]
        );

        // If merchant exists, ensure it's active
        if (!$merchant->is_active) {
            $merchant->update(['is_active' => true]);
        }

        // Store tokens
        $this->store->put($sallaMerchantId, $accessToken, $refreshToken, $expiresAt);

        Log::info('Tokens stored successfully', [
            'merchant_id' => $sallaMerchantId,
            'expires_at' => $expiresAt,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Handle app.installed event
     * Optional: track installations
     */
    public function installed(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        Log::info('Received app.installed event', ['payload' => $payload]);

        $sallaMerchantId = data_get($payload, 'data.store.id');

        if ($sallaMerchantId) {
            // Ensure merchant placeholder exists
            Merchant::firstOrCreate(
                ['salla_merchant_id' => $sallaMerchantId],
                ['is_active' => true]
            );
        }

        return response()->json(['ok' => true]);
    }
}
