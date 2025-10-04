<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\Salla\OAuthTokenStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppEventsController extends Controller
{
    public function __construct(
        private OAuthTokenStore $tokenStore
    ) {}

    public function authorized(Request $request)
    {
        $payload = $request->all();
        
        Log::info('App authorized event received', $payload);

        $merchantId = $payload['data']['store']['id'] ?? null;
        $accessToken = $payload['data']['access_token'] ?? null;
        $refreshToken = $payload['data']['refresh_token'] ?? null;
        $expiresIn = $payload['data']['expires_in'] ?? 3600;

        if (!$merchantId || !$accessToken || !$refreshToken) {
            Log::error('Invalid app authorized payload', $payload);
            return response('Invalid payload', 400);
        }

        try {
            // Find or create merchant
            $merchant = Merchant::where('salla_merchant_id', $merchantId)->first();
            
            if (!$merchant) {
                // Create merchant if not exists
                $merchant = Merchant::create([
                    'salla_merchant_id' => $merchantId,
                    'store_name' => $payload['data']['store']['name'] ?? 'Unknown Store',
                    'email' => $payload['data']['store']['email'] ?? "store_{$merchantId}@example.com",
                    'password' => bcrypt('temp_password_' . time()),
                    'is_approved' => false, // Requires admin approval
                ]);
            }

            // Store tokens
            $this->tokenStore->store($merchantId, $accessToken, $refreshToken, $expiresIn);

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Failed to process app authorized event', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            return response('Internal error', 500);
        }
    }
}
