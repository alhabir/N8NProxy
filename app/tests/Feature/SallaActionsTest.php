<?php

use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\SallaActionAudit;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Create test merchant
    $this->merchant = Merchant::create([
        'salla_merchant_id' => '112233',
        'store_name' => 'Test Store',
        'is_active' => true,
    ]);

    // Create test token
    $this->token = MerchantToken::create([
        'merchant_id' => $this->merchant->id,
        'salla_merchant_id' => '112233',
        'access_token' => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'access_token_expires_at' => now()->addHour(),
    ]);

    // Set test bearer token
    config(['app.actions_token' => 'test_bearer_token']);
});

test('actions endpoints require authentication', function () {
    $response = $this->getJson('/api/actions/orders/list?merchant_id=112233');
    
    expect($response->status())->toBe(401)
        ->and($response->json())->toHaveKey('error');
});

test('can list orders with valid auth', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 1, 'status' => 'pending'],
            ],
        ], 200),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->getJson('/api/actions/orders/list?merchant_id=112233');

    expect($response->status())->toBe(200)
        ->and($response->json())->toHaveKey('data');

    // Check audit was created
    $audit = SallaActionAudit::first();
    expect($audit)->not->toBeNull()
        ->and($audit->resource)->toBe('orders')
        ->and($audit->action)->toBe('list')
        ->and($audit->method)->toBe('GET');
});

test('can create order', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response([
            'status' => 201,
            'data' => ['id' => 123, 'status' => 'pending'],
        ], 201),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->postJson('/api/actions/orders/create', [
            'merchant_id' => '112233',
            'payload' => [
                'customer_id' => 456,
                'items' => [
                    ['product_id' => 789, 'quantity' => 1],
                ],
            ],
        ]);

    expect($response->status())->toBe(201)
        ->and($response->json())->toHaveKey('data');
});

test('can get single order', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response([
            'status' => 200,
            'data' => ['id' => 123, 'status' => 'completed'],
        ], 200),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->getJson('/api/actions/orders/get?merchant_id=112233&order_id=123');

    expect($response->status())->toBe(200)
        ->and($response->json()['data']['id'])->toBe(123);
});

test('can update order', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response([
            'status' => 200,
            'data' => ['id' => 123, 'status' => 'shipped'],
        ], 200),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->patchJson('/api/actions/orders/update', [
            'merchant_id' => '112233',
            'order_id' => 123,
            'payload' => ['status' => 'shipped'],
        ]);

    expect($response->status())->toBe(200);
});

test('can delete order', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response([], 204),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->deleteJson('/api/actions/orders/delete', [
            'merchant_id' => '112233',
            'order_id' => 123,
        ]);

    expect($response->status())->toBe(204);
});

test('can create product', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response([
            'status' => 201,
            'data' => ['id' => 999, 'name' => 'Test Product'],
        ], 201),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->postJson('/api/actions/products/create', [
            'merchant_id' => '112233',
            'payload' => [
                'name' => 'Test Product',
                'price' => 99.99,
                'quantity' => 10,
            ],
        ]);

    expect($response->status())->toBe(201)
        ->and($response->json()['data']['name'])->toBe('Test Product');
});

test('can list products', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 1, 'name' => 'Product 1'],
                ['id' => 2, 'name' => 'Product 2'],
            ],
        ], 200),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->getJson('/api/actions/products/list?merchant_id=112233');

    expect($response->status())->toBe(200)
        ->and($response->json()['data'])->toHaveCount(2);
});

test('handles 401 and refreshes token', function () {
    // First request returns 401, second succeeds after refresh
    Http::fake([
        'api.salla.dev/admin/v2/orders*' => Http::sequence()
            ->push([], 401)
            ->push(['status' => 200, 'data' => []], 200),
        'accounts.salla.sa/oauth2/token' => Http::response([
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
        ], 200),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->getJson('/api/actions/orders/list?merchant_id=112233');

    expect($response->status())->toBe(200);

    // Verify token was updated
    $this->token->refresh();
    expect($this->token->access_token)->toBe('new_access_token');
});

test('validates required fields', function () {
    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->getJson('/api/actions/orders/list');

    expect($response->status())->toBe(422)
        ->and($response->json())->toHaveKey('errors');
});

test('can create coupon', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response([
            'status' => 201,
            'data' => ['id' => 100, 'code' => 'SAVE20'],
        ], 201),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->postJson('/api/actions/marketing/coupons/create', [
            'merchant_id' => '112233',
            'payload' => [
                'code' => 'SAVE20',
                'type' => 'percentage',
                'amount' => 20,
            ],
        ]);

    expect($response->status())->toBe(201);
});

test('can create export job', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response([
            'status' => 201,
            'data' => ['id' => 50, 'status' => 'pending'],
        ], 201),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->postJson('/api/actions/exports/create', [
            'merchant_id' => '112233',
            'payload' => [
                'type' => 'orders',
                'format' => 'csv',
            ],
        ]);

    expect($response->status())->toBe(201);
});

test('audits are created for all requests', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response(['data' => []], 200),
    ]);

    $initialCount = SallaActionAudit::count();

    $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->getJson('/api/actions/orders/list?merchant_id=112233');

    $this->withHeader('Authorization', 'Bearer test_bearer_token')
        ->getJson('/api/actions/products/list?merchant_id=112233');

    expect(SallaActionAudit::count())->toBe($initialCount + 2);
});
