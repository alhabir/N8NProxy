<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\User;
use App\Services\Salla\OAuthTokenStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AppEventsController extends Controller
{
    public function __construct(
        private OAuthTokenStore $tokenStore
    ) {}

    public function authorized(Request $request)
    {
        $payload = $request->all();
        
        Log::info('App authorized event received', $payload);

        $merchantId = data_get($payload, 'data.store.id');
        $storeName = data_get($payload, 'data.store.name');
        $email = Str::lower(data_get($payload, 'data.store.email') ?? "store_{$merchantId}@example.com");
        $accessToken = data_get($payload, 'data.access_token');
        $refreshToken = data_get($payload, 'data.refresh_token');
        $expiresIn = (int) data_get($payload, 'data.expires_in', 3600);

        if (!$merchantId || !$accessToken || !$refreshToken) {
            Log::warning('Missing required data in app authorized payload', ['payload' => $payload]);

            return response()->json(['error' => 'Missing required data'], 422);
        }

        try {
            DB::transaction(function () use ($merchantId, $storeName, $email, $accessToken, $refreshToken, $expiresIn) {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $storeName ?? 'Salla Merchant',
                        'password' => Hash::make(Str::password(16)),
                        'is_admin' => false,
                    ]
                );

                $merchant = Merchant::where('salla_merchant_id', $merchantId)->first();

                if (! $merchant) {
                    $merchant = Merchant::where('user_id', $user->id)->orWhere('email', $email)->first();
                }

                if (! $merchant) {
                    $merchant = new Merchant([
                        'salla_merchant_id' => $merchantId,
                    ]);
                    $merchant->is_active = true;
                    $merchant->is_approved = false;
                }

                $merchant->user_id = $user->id;
                $merchant->salla_merchant_id = $merchantId;
                $merchant->email = $email;

                if ($storeName) {
                    $merchant->store_name = $storeName;
                }

                $merchant->save();

                $this->tokenStore->store($merchantId, $accessToken, $refreshToken, $expiresIn);
            });
        } catch (Throwable $e) {
            Log::error('Failed to process app authorized event', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }

        return response()->json(['ok' => true]);
    }
}
