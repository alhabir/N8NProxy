<?php

use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Services\Salla\SallaHttpClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Create a test merchant and token
    $this->merchant = Merchant::factory()->create([
        'salla_merchant_id' => 'test_merchant_123',
        'is_active' => true,
    ]);

    $this->merchantToken = MerchantToken::create([
        'merchant_id' => $this->merchant->id,
        'salla_merchant_id' => $this->merchant->salla_merchant_id,
        'access_token' => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'access_token_expires_at' => now()->addHour(),
    ]);

    // Set up API auth token
    config(['app.actions_token' => 'test_actions_token']);
});

it('requires bearer token for actions endpoints', function () {
    $response = $this->postJson('/api/actions/orders/create', [
        'merchant_id' => 'test_merchant_123',
        'payload' => ['test' => 'data'],
    ]);

    $response->assertStatus(401)
        ->assertJson(['error' => 'Unauthorized']);
});

it('accepts valid bearer token for actions endpoints', function () {
    Http::fake([
        'api.salla.dev/*' => Http::response(['data' => ['id' => 123]], 200),
    ]);

    $response = $this->postJson('/api/actions/orders/create', [
        'merchant_id' => 'test_merchant_123',
        'payload' => ['name' => 'Test Order'],
    ], [
        'Authorization' => 'Bearer test_actions_token',
    ]);

    $response->assertStatus(200);
});

it('validates merchant_id is required', function () {
    $response = $this->withToken('test_actions_token')
        ->postJson('/api/actions/orders/create', [
            'payload' => ['test' => 'data'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['merchant_id']);
});

it('can create an order', function () {
    Http::fake([
        'api.salla.dev/admin/v2/orders' => Http::response([
            'data' => ['id' => 123, 'status' => 'pending'],
        ], 201),
    ]);

    $response = $this->withToken('test_actions_token')
        ->postJson('/api/actions/orders/create', [
            'merchant_id' => 'test_merchant_123',
            'payload' => [
                'customer_id' => 456,
                'items' => [['product_id' => 789, 'quantity' => 1]],
            ],
        ]);

    $response->assertStatus(201)
        ->assertJson(['data' => ['id' => 123]]);

    // Verify the HTTP request was made correctly
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.salla.dev/admin/v2/orders'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test_access_token')
            && $request['customer_id'] === 456;
    });
});

it('can list orders with query parameters', function () {
    Http::fake([
        'api.salla.dev/admin/v2/orders*' => Http::response([
            'data' => [['id' => 123], ['id' => 124]],
            'pagination' => ['total' => 2],
        ], 200),
    ]);

    $response = $this->withToken('test_actions_token')
        ->getJson('/api/actions/orders/list?' . http_build_query([
            'merchant_id' => 'test_merchant_123',
            'page' => 1,
            'per_page' => 20,
            'status' => 'pending',
        ]));

    $response->assertStatus(200)
        ->assertJson(['data' => [['id' => 123], ['id' => 124]]]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'page=1')
            && str_contains($request->url(), 'status=pending');
    });
});

it('can get a specific order', function () {
    Http::fake([
        'api.salla.dev/admin/v2/orders/123' => Http::response([
            'data' => ['id' => 123, 'status' => 'completed'],
        ], 200),
    ]);

    $response = $this->withToken('test_actions_token')
        ->getJson('/api/actions/orders/get?' . http_build_query([
            'merchant_id' => 'test_merchant_123',
            'order_id' => 123,
        ]));

    $response->assertStatus(200)
        ->assertJson(['data' => ['id' => 123]]);
});

it('can update an order', function () {
    Http::fake([
        'api.salla.dev/admin/v2/orders/123' => Http::response([
            'data' => ['id' => 123, 'status' => 'updated'],
        ], 200),
    ]);

    $response = $this->withToken('test_actions_token')
        ->patchJson('/api/actions/orders/update', [
            'merchant_id' => 'test_merchant_123',
            'order_id' => 123,
            'payload' => ['status' => 'completed'],
        ]);

    $response->assertStatus(200);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.salla.dev/admin/v2/orders/123'
            && $request->method() === 'PATCH'
            && $request['status'] === 'completed';
    });
});

it('can delete an order', function () {
    Http::fake([
        'api.salla.dev/admin/v2/orders/123' => Http::response(null, 204),
    ]);

    $response = $this->withToken('test_actions_token')
        ->deleteJson('/api/actions/orders/delete', [
            'merchant_id' => 'test_merchant_123',
            'order_id' => 123,
        ]);

    $response->assertStatus(204);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.salla.dev/admin/v2/orders/123'
            && $request->method() === 'DELETE';
    });
});

it('handles 401 and refreshes token automatically', function () {
    // First request returns 401, second succeeds after refresh
    Http::fake([
        'api.salla.dev/admin/v2/orders' => Http::sequence()
            ->push(null, 401) // First request fails
            ->push(['data' => ['id' => 123]], 201), // Retry succeeds
        'accounts.salla.sa/oauth2/token' => Http::response([
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
        ], 200),
    ]);

    $response = $this->withToken('test_actions_token')
        ->postJson('/api/actions/orders/create', [
            'merchant_id' => 'test_merchant_123',
            'payload' => ['customer_id' => 456],
        ]);

    $response->assertStatus(201);

    // Verify refresh token request was made
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'accounts.salla.sa')
            && $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'test_refresh_token';
    });

    // Verify token was updated in database
    $this->merchantToken->refresh();
    expect($this->merchantToken->access_token)->toBe('new_access_token');
});

it('audits all API calls', function () {
    Http::fake([
        'api.salla.dev/admin/v2/orders' => Http::response(['data' => ['id' => 123]], 201),
    ]);

    $this->withToken('test_actions_token')
        ->postJson('/api/actions/orders/create', [
            'merchant_id' => 'test_merchant_123',
            'payload' => ['customer_id' => 456],
        ]);

    $this->assertDatabaseHas('salla_action_audits', [
        'salla_merchant_id' => 'test_merchant_123',
        'resource' => 'orders',
        'action' => 'create',
        'method' => 'POST',
        'status_code' => 201,
    ]);
});

function withToken(string $token)
{
    return test()->withHeaders(['Authorization' => "Bearer {$token}"]);
}