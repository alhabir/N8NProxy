<?php

use App\Models\Merchant;
use App\Models\MerchantToken;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('app.actions_token', 'test_token');
});

it('creates product', function () {
    $merchant = Merchant::create([
        'salla_merchant_id' => '112233',
        'n8n_base_url' => 'https://example.com',
        'is_active' => true,
    ]);
    MerchantToken::create([
        'merchant_id' => $merchant->id,
        'salla_merchant_id' => '112233',
        'access_token' => 'access-1',
        'refresh_token' => 'refresh-1',
        'access_token_expires_at' => now()->addHour(),
    ]);

    Http::fake([
        'api.salla.dev/*' => Http::response(['id' => 1], 201),
    ]);

    $res = $this->withHeader('Authorization', 'Bearer test_token')
        ->postJson('/api/actions/products/create', [
            'merchant_id' => '112233',
            'payload' => [
                'name' => 'New Product',
                'sku' => 'SKU-001',
                'price' => 199.00,
                'quantity' => 10,
            ],
        ]);

    $res->assertStatus(201);
});
