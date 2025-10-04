<?php

use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\SallaActionAudit;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('app.actions_token', 'test_token');
});

function seedMerchantWithTokens(string $sallaId = '112233'): array {
    $merchant = Merchant::create([
        'salla_merchant_id' => $sallaId,
        'n8n_base_url' => 'https://example.com',
        'is_active' => true,
    ]);
    MerchantToken::create([
        'merchant_id' => $merchant->id,
        'salla_merchant_id' => $sallaId,
        'access_token' => 'access-1',
        'refresh_token' => 'refresh-1',
        'access_token_expires_at' => now()->addHour(),
    ]);
    return [$merchant, $sallaId];
}

it('lists orders and audits call', function () {
    [$merchant, $sallaId] = seedMerchantWithTokens();

    Http::fake([
        'api.salla.dev/*' => Http::response(['data' => []], 200),
    ]);

    $res = $this->withHeader('Authorization', 'Bearer test_token')
        ->getJson('/api/actions/orders/list?merchant_id='.$sallaId.'&page=1&per_page=20');

    $res->assertOk();
    expect(SallaActionAudit::count())->toBe(1);
    $audit = SallaActionAudit::first();
    expect($audit->resource)->toBe('orders');
    expect($audit->action)->toBe('list');
});

it('retries on 401 by refreshing tokens', function () {
    [$merchant, $sallaId] = seedMerchantWithTokens();

    Http::fake([
        'api.salla.dev/*' => Http::sequence()
            ->push(['error' => 'unauthorized'], 401)
            ->push(['data' => ['ok' => true]], 200),
        (string) config('salla_api.oauth.token_url') => Http::response([
            'access_token' => 'access-2',
            'refresh_token' => 'refresh-2',
            'expires_in' => 3600,
        ], 200),
    ]);

    $res = $this->withHeader('Authorization', 'Bearer test_token')
        ->getJson('/api/actions/orders/get?merchant_id='.$sallaId.'&order_id=555');

    $res->assertOk();
    expect(SallaActionAudit::count())->toBe(1);
});
