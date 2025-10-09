<?php

namespace Tests\Feature\Actions;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SallaProxyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.actions_token' => 'proxy_token']);
        config(['salla_api.base' => 'https://api.salla.test/admin/v2']);

        $this->withServerVariables([
            'HTTP_HOST' => config('panels.admin_domain'),
            'SERVER_NAME' => config('panels.admin_domain'),
        ]);
    }

    public function test_proxy_forwards_request_with_merchant_tokens(): void
    {
        $merchant = Merchant::create([
            'store_id' => 'store-777',
            'email' => 'owner@example.com',
            'salla_merchant_id' => '777',
            'store_name' => 'Proxy Store',
            'salla_access_token' => 'direct_access',
            'salla_refresh_token' => 'direct_refresh',
            'salla_token_expires_at' => now()->addHour(),
            'is_active' => true,
            'is_approved' => true,
            'connected_at' => now(),
        ]);

        MerchantToken::create([
            'merchant_id' => $merchant->id,
            'salla_merchant_id' => '777',
            'access_token' => 'merchant_access_token',
            'refresh_token' => 'merchant_refresh_token',
            'access_token_expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'https://api.salla.test/admin/v2/orders?limit=1' => Http::response(['data' => []], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer proxy_token')
            ->postJson('/api/actions/salla', [
                'merchant_id' => '777',
                'method' => 'GET',
                'path' => '/orders?limit=1',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'status' => 200,
            ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.salla.test/admin/v2/orders?limit=1'
                && $request->header('Authorization')[0] === 'Bearer merchant_access_token';
        });
    }
}
