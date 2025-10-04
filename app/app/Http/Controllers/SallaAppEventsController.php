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
        private OAuthTokenStore $tokenStore
    ) {}

    /**
     * Handle app.store.authorize event
     * This event is sent when a merchant authorizes your app
     */
    public function authorized(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        
        // Extract data from the event payload
        $sallaMerchantId = data_get($payload, 'data.store.id');
        $storeName = data_get($payload, 'data.store.name');
        $accessToken = data_get($payload, 'data.tokens.access_token');
        $refreshToken = data_get($payload, 'data.tokens.refresh_token');
        $expiresIn = (int) data_get($payload, 'data.tokens.expires_in', 3600);
        
        if (!$sallaMerchantId || !$accessToken || !$refreshToken) {
            Log::error('Missing required data in app.store.authorize event', $payload);
            return response()->json(['error' => 'Missing required data'], 400);
        }

        // Calculate expiration time
        $expiresAt = now()->addSeconds($expiresIn);
        
        // Ensure merchant exists and is active
        $merchant = Merchant::firstOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            [
                'store_name' => $storeName ?: 'Store ' . $sallaMerchantId,
                'is_active' => true,
            ]
        );
        
        // Update store name if provided
        if ($storeName && $merchant->store_name !== $storeName) {
            $merchant->update(['store_name' => $storeName]);
        }
        
        // Store tokens
        $this->tokenStore->put($sallaMerchantId, $accessToken, $refreshToken, $expiresAt);
        
        Log::info('App authorized for merchant', [
            'merchant_id' => $sallaMerchantId,
            'store_name' => $storeName,
            'expires_at' => $expiresAt,
        ]);
        
        return response()->json(['ok' => true]);
    }

    /**
     * Handle app.installed event
     * This event is sent when a merchant installs your app
     */
    public function installed(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        
        $sallaMerchantId = data_get($payload, 'data.store.id');
        $storeName = data_get($payload, 'data.store.name');
        
        if (!$sallaMerchantId) {
            Log::error('Missing store ID in app.installed event', $payload);
            return response()->json(['error' => 'Missing store ID'], 400);
        }
        
        // Ensure merchant placeholder exists
        $merchant = Merchant::firstOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            [
                'store_name' => $storeName ?: 'Store ' . $sallaMerchantId,
                'is_active' => true,
            ]
        );
        
        Log::info('App installed for merchant', [
            'merchant_id' => $sallaMerchantId,
            'store_name' => $storeName,
        ]);
        
        return response()->json(['ok' => true]);
    }
}