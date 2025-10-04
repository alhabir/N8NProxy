<?php

use App\Models\Merchant;
use Illuminate\Support\Facades\Http;

it('handles app authorization event', function () {
    $payload = [
        'event' => 'app.store.authorize',
        'data' => [
            'store' => ['id' => 'merchant_123'],
            'tokens' => [
                'access_token' => 'access_token_value',
                'refresh_token' => 'refresh_token_value',
                'expires_in' => 3600,
            ],
        ],
    ];

    $response = $this->postJson('/api/app-events/authorized', $payload);

    $response->assertStatus(200)
        ->assertJson(['ok' => true]);

    // Verify merchant was created/updated
    $merchant = Merchant::where('salla_merchant_id', 'merchant_123')->first();
    expect($merchant)->not->toBeNull();
    expect($merchant->is_active)->toBeTrue();

    // Verify token was stored
    $token = $merchant->token;
    expect($token)->not->toBeNull();
    expect($token->access_token)->toBe('access_token_value');
});

it('handles app installation event', function () {
    $payload = [
        'event' => 'app.store.installed',
        'data' => [
            'store' => ['id' => 'merchant_456'],
        ],
    ];

    $response = $this->postJson('/api/app-events/installed', $payload);

    $response->assertStatus(200)
        ->assertJson(['ok' => true]);

    // Verify merchant placeholder was created
    $merchant = Merchant::where('salla_merchant_id', 'merchant_456')->first();
    expect($merchant)->not->toBeNull();
});

it('handles malformed authorization payload', function () {
    $payload = [
        'event' => 'app.store.authorize',
        'data' => [
            'store' => ['id' => null], // Missing store ID
        ],
    ];

    $response = $this->postJson('/api/app-events/authorized', $payload);

    $response->assertStatus(400)
        ->assertJson(['error' => 'Invalid payload']);
});