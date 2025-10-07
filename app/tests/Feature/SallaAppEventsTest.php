<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SallaAppEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables([
            'HTTP_HOST' => config('panels.admin_domain'),
            'SERVER_NAME' => config('panels.admin_domain'),
        ]);
    }

    /**
     * Test app.store.authorize event handling
     */
    public function test_app_store_authorize_event_captures_tokens(): void
    {
        $payload = [
            'event' => 'app.store.authorize',
            'data' => [
                'store' => [
                    'id' => '789456',
                    'name' => 'My Test Store',
                ],
                'tokens' => [
                    'access_token' => 'salla_access_token_123',
                    'refresh_token' => 'salla_refresh_token_456',
                    'expires_in' => 7200,
                ],
            ],
        ];

        $response = $this->postJson('/app-events/authorized', $payload);

        $response->assertStatus(200)
            ->assertJson(['ok' => true]);

        // Verify merchant was created
        $merchant = Merchant::where('salla_merchant_id', '789456')->first();
        $this->assertNotNull($merchant);
        $this->assertEquals('My Test Store', $merchant->store_name);
        $this->assertTrue($merchant->is_active);
        $this->assertNotNull($merchant->user_id);

        // Verify token was stored
        $token = MerchantToken::where('salla_merchant_id', '789456')->first();
        $this->assertNotNull($token);
        $this->assertEquals($merchant->id, $token->merchant_id);
        $this->assertEquals('salla_access_token_123', $token->access_token);
        $this->assertEquals('salla_refresh_token_456', $token->refresh_token);
        $this->assertNotNull($token->access_token_expires_at);
    }

    /**
     * Test app.store.authorize updates existing merchant
     */
    public function test_app_store_authorize_updates_existing_merchant(): void
    {
        $user = User::factory()->create([
            'email' => 'existing789456@example.com',
            'name' => 'Old Store Name',
        ]);

        // Create existing merchant
        $merchant = Merchant::create([
            'store_id' => 'store-789456',
            'user_id' => $user->id,
            'email' => $user->email,
            'salla_merchant_id' => '789456',
            'store_name' => 'Old Store Name',
            'n8n_base_url' => 'https://example.com',
            'n8n_webhook_path' => '/webhook/salla',
            'is_active' => true,
        ]);

        $payload = [
            'event' => 'app.store.authorize',
            'data' => [
                'store' => [
                    'id' => '789456',
                    'name' => 'Updated Store Name',
                ],
                'tokens' => [
                    'access_token' => 'new_access_token',
                    'refresh_token' => 'new_refresh_token',
                    'expires_in' => 3600,
                ],
            ],
        ];

        $response = $this->postJson('/app-events/authorized', $payload);

        $response->assertStatus(200)->assertJson(['ok' => true]);

        // Verify merchant was updated
        $merchant->refresh();
        $this->assertEquals('Updated Store Name', $merchant->store_name);

        // Verify token was created/updated
        $token = MerchantToken::where('merchant_id', $merchant->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals('new_access_token', $token->access_token);
    }

    /**
     * Test app.installed event handling
     */
    public function test_app_installed_event_creates_merchant_placeholder(): void
    {
        $payload = [
            'event' => 'app.installed',
            'data' => [
                'store' => [
                    'id' => '654321',
                    'name' => 'New Install Store',
                ],
            ],
        ];

        $response = $this->postJson('/app-events/installed', $payload);

        $response->assertStatus(200)
            ->assertJson(['ok' => true]);

        // Verify merchant was created
        $merchant = Merchant::where('salla_merchant_id', '654321')->first();
        $this->assertNotNull($merchant);
        $this->assertEquals('New Install Store', $merchant->store_name);
        $this->assertTrue($merchant->is_active);
    }

    /**
     * Test missing data returns error
     */
    public function test_missing_required_data_returns_error(): void
    {
        $payload = [
            'event' => 'app.store.authorize',
            'data' => [
                'store' => [
                    'id' => '789456',
                ],
                // Missing tokens
            ],
        ];

        $response = $this->postJson('/app-events/authorized', $payload);

        $response->assertStatus(422)
            ->assertJson(['error' => 'Missing required data']);
    }
}
