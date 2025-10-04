<?php

use App\Models\Merchant;
use App\Models\MerchantToken;

test('can handle app authorized event', function () {
    $payload = [
        'event' => 'app.store.authorize',
        'data' => [
            'store' => [
                'id' => 'store123',
                'name' => 'My Store',
            ],
            'tokens' => [
                'access_token' => 'access_token_value',
                'refresh_token' => 'refresh_token_value',
                'expires_in' => 3600,
            ],
        ],
    ];

    $response = $this->postJson('/api/app-events/authorized', $payload);

    expect($response->status())->toBe(200)
        ->and($response->json())->toHaveKey('ok');

    // Check merchant was created
    $merchant = Merchant::where('salla_merchant_id', 'store123')->first();
    expect($merchant)->not->toBeNull()
        ->and($merchant->is_active)->toBeTrue();

    // Check token was stored
    $token = MerchantToken::where('salla_merchant_id', 'store123')->first();
    expect($token)->not->toBeNull()
        ->and($token->access_token)->toBe('access_token_value')
        ->and($token->refresh_token)->toBe('refresh_token_value');
});

test('can handle app installed event', function () {
    $payload = [
        'event' => 'app.installed',
        'data' => [
            'store' => [
                'id' => 'store456',
                'name' => 'New Store',
            ],
        ],
    ];

    $response = $this->postJson('/api/app-events/installed', $payload);

    expect($response->status())->toBe(200);

    // Check merchant was created
    $merchant = Merchant::where('salla_merchant_id', 'store456')->first();
    expect($merchant)->not->toBeNull();
});

test('reactivates inactive merchant on authorization', function () {
    // Create inactive merchant
    $merchant = Merchant::create([
        'salla_merchant_id' => 'store789',
        'is_active' => false,
    ]);

    $payload = [
        'event' => 'app.store.authorize',
        'data' => [
            'store' => ['id' => 'store789'],
            'tokens' => [
                'access_token' => 'token1',
                'refresh_token' => 'token2',
                'expires_in' => 3600,
            ],
        ],
    ];

    $response = $this->postJson('/api/app-events/authorized', $payload);

    expect($response->status())->toBe(200);

    $merchant->refresh();
    expect($merchant->is_active)->toBeTrue();
});

test('handles missing required fields in authorize event', function () {
    $payload = [
        'event' => 'app.store.authorize',
        'data' => [
            'store' => ['id' => 'store999'],
            'tokens' => [
                'access_token' => 'token1',
                // Missing refresh_token
            ],
        ],
    ];

    $response = $this->postJson('/api/app-events/authorized', $payload);

    expect($response->status())->toBe(400);
});
