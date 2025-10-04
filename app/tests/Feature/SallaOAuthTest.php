<?php

use App\Models\Merchant;
use App\Services\Salla\OAuthTokenStore;
use App\Services\Salla\OAuthRefresher;
use Illuminate\Support\Facades\Http;

it('can store OAuth tokens for a merchant', function () {
    $store = app(OAuthTokenStore::class);
    
    $sallaMerchantId = 'test_merchant_123';
    $accessToken = 'access_token_value';
    $refreshToken = 'refresh_token_value';
    $expiresAt = now()->addHours(2);

    $merchantToken = $store->put($sallaMerchantId, $accessToken, $refreshToken, $expiresAt);

    expect($merchantToken)->not->toBeNull();
    expect($merchantToken->salla_merchant_id)->toBe($sallaMerchantId);
    expect($merchantToken->access_token)->toBe($accessToken);
    expect($merchantToken->refresh_token)->toBe($refreshToken);
    
    // Verify merchant was created
    $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
    expect($merchant)->not->toBeNull();
    expect($merchant->is_active)->toBeTrue();
});

it('can retrieve tokens by salla merchant id', function () {
    $store = app(OAuthTokenStore::class);
    
    $sallaMerchantId = 'test_merchant_456';
    $store->put($sallaMerchantId, 'access', 'refresh', now()->addHour());

    $token = $store->get($sallaMerchantId);
    
    expect($token)->not->toBeNull();
    expect($token->salla_merchant_id)->toBe($sallaMerchantId);
});

it('can refresh OAuth tokens', function () {
    config([
        'salla_api.oauth.token_url' => 'https://accounts.salla.sa/oauth2/token',
        'salla_api.oauth.client_id' => 'test_client_id',
        'salla_api.oauth.client_secret' => 'test_client_secret',
    ]);

    Http::fake([
        'accounts.salla.sa/oauth2/token' => Http::response([
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
        ], 200),
    ]);

    $refresher = app(OAuthRefresher::class);
    
    $result = $refresher->refresh('test_merchant', 'old_refresh_token');

    expect($result)->toHaveKeys(['access_token', 'refresh_token', 'expires_at']);
    expect($result['access_token'])->toBe('new_access_token');
    expect($result['refresh_token'])->toBe('new_refresh_token');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://accounts.salla.sa/oauth2/token'
            && $request['grant_type'] === 'refresh_token'
            && $request['client_id'] === 'test_client_id'
            && $request['refresh_token'] === 'old_refresh_token';
    });
});

it('handles refresh token failure', function () {
    config([
        'salla_api.oauth.token_url' => 'https://accounts.salla.sa/oauth2/token',
        'salla_api.oauth.client_id' => 'test_client_id',
        'salla_api.oauth.client_secret' => 'test_client_secret',
    ]);

    Http::fake([
        'accounts.salla.sa/oauth2/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    $refresher = app(OAuthRefresher::class);
    
    expect(fn() => $refresher->refresh('test_merchant', 'invalid_refresh_token'))
        ->toThrow(Exception::class);
});