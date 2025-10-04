<?php

namespace Tests\Feature\Actions;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrdersControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test token
        config(['app.actions_token' => 'test_token']);
        config(['salla_api.base' => 'https://api.salla.test/admin/v2']);
    }

    /**
     * Create a test merchant with tokens
     */
    private function createMerchantWithToken(): Merchant
    {
        $merchant = Merchant::create([
            'salla_merchant_id' => '123456',
            'store_name' => 'Test Store',
            'n8n_base_url' => 'https://example.com',
            'is_active' => true,
        ]);

        MerchantToken::create([
            'merchant_id' => $merchant->id,
            'salla_merchant_id' => '123456',
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'access_token_expires_at' => now()->addHour(),
        ]);

        return $merchant;
    }

    /**
     * Test create order
     */
    public function test_create_order(): void
    {
        $this->createMerchantWithToken();
        
        Http::fake([
            'https://api.salla.test/admin/v2/orders' => Http::response([
                'data' => [
                    'id' => 987654,
                    'status' => 'pending',
                    'total' => 199.99,
                ],
            ], 201),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer test_token')
            ->postJson('/api/actions/orders/create', [
                'merchant_id' => '123456',
                'payload' => [
                    'customer_id' => 111,
                    'products' => [
                        ['id' => 222, 'quantity' => 2],
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.id', 987654);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.salla.test/admin/v2/orders' &&
                   $request->method() === 'POST' &&
                   $request->header('Authorization')[0] === 'Bearer test_access_token' &&
                   $request['customer_id'] === 111;
        });
    }

    /**
     * Test get order
     */
    public function test_get_order(): void
    {
        $this->createMerchantWithToken();
        
        Http::fake([
            'https://api.salla.test/admin/v2/orders/987654' => Http::response([
                'data' => [
                    'id' => 987654,
                    'status' => 'completed',
                    'total' => 299.99,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer test_token')
            ->getJson('/api/actions/orders/get?merchant_id=123456&order_id=987654');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', 987654)
            ->assertJsonPath('data.status', 'completed');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.salla.test/admin/v2/orders/987654' &&
                   $request->method() === 'GET';
        });
    }

    /**
     * Test list orders with filters
     */
    public function test_list_orders_with_filters(): void
    {
        $this->createMerchantWithToken();
        
        Http::fake([
            'https://api.salla.test/admin/v2/orders?page=1&per_page=20&status=completed' => Http::response([
                'data' => [
                    ['id' => 1, 'status' => 'completed'],
                    ['id' => 2, 'status' => 'completed'],
                ],
                'pagination' => [
                    'current_page' => 1,
                    'total' => 50,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer test_token')
            ->getJson('/api/actions/orders/list?merchant_id=123456&page=1&per_page=20&status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'status=completed') &&
                   str_contains($request->url(), 'page=1') &&
                   str_contains($request->url(), 'per_page=20');
        });
    }

    /**
     * Test update order
     */
    public function test_update_order(): void
    {
        $this->createMerchantWithToken();
        
        Http::fake([
            'https://api.salla.test/admin/v2/orders/987654' => Http::response([
                'data' => [
                    'id' => 987654,
                    'status' => 'shipped',
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer test_token')
            ->patchJson('/api/actions/orders/update', [
                'merchant_id' => '123456',
                'order_id' => '987654',
                'payload' => [
                    'status' => 'shipped',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'shipped');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.salla.test/admin/v2/orders/987654' &&
                   $request->method() === 'PATCH' &&
                   $request['status'] === 'shipped';
        });
    }

    /**
     * Test delete order
     */
    public function test_delete_order(): void
    {
        $this->createMerchantWithToken();
        
        Http::fake([
            'https://api.salla.test/admin/v2/orders/987654' => Http::response(null, 204),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer test_token')
            ->deleteJson('/api/actions/orders/delete?merchant_id=123456&order_id=987654');

        $response->assertStatus(204);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.salla.test/admin/v2/orders/987654' &&
                   $request->method() === 'DELETE';
        });
    }

    /**
     * Test token refresh on 401
     */
    public function test_token_refresh_on_401(): void
    {
        $this->createMerchantWithToken();
        
        // First request returns 401
        // Token refresh request
        // Retry request succeeds
        Http::fake([
            'https://api.salla.test/admin/v2/orders' => Http::sequence()
                ->push(null, 401)
                ->push(['data' => ['id' => 123]], 200),
            'https://accounts.salla.sa/oauth2/token' => Http::response([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer test_token')
            ->getJson('/api/actions/orders/list?merchant_id=123456');

        $response->assertStatus(200);

        // Verify token refresh was called
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://accounts.salla.sa/oauth2/token' &&
                   $request['grant_type'] === 'refresh_token' &&
                   $request['refresh_token'] === 'test_refresh_token';
        });
    }
}