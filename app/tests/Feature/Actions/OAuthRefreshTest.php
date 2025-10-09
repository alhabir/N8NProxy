<?php

namespace Tests\Feature\Actions;

use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\User;
use App\Services\Salla\OAuthRefresher;
use App\Services\Salla\OAuthTokenStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthRefreshTest extends TestCase
{
    use RefreshDatabase;

    protected OAuthTokenStore $tokenStore;
    protected OAuthRefresher $refresher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tokenStore = app(OAuthTokenStore::class);
        $this->refresher = app(OAuthRefresher::class);
        
        config([
            'salla_api.oauth.token_url' => 'https://accounts.salla.test/oauth2/token',
            'salla_api.oauth.client_id' => 'test_client_id',
            'salla_api.oauth.client_secret' => 'test_client_secret',
        ]);

        $this->withServerVariables([
            'HTTP_HOST' => config('panels.admin_domain'),
            'SERVER_NAME' => config('panels.admin_domain'),
        ]);
    }

    /**
     * Test token refresh flow
     */
    public function test_token_refresh_updates_stored_tokens(): void
    {
        // Create merchant with expiring token
        $user = User::factory()->create([
            'email' => 'merchant111222@example.com',
            'name' => 'Test Store',
        ]);

        $merchant = Merchant::create([
            'store_id' => 'store-111222',
            'claimed_by_user_id' => $user->id,
            'email' => $user->email,
            'salla_merchant_id' => '111222',
            'store_name' => 'Test Store',
            'n8n_base_url' => 'https://example.com',
            'n8n_webhook_path' => '/webhook/salla',
            'is_active' => true,
            'is_approved' => true,
        ]);

        MerchantToken::create([
            'merchant_id' => $merchant->id,
            'salla_merchant_id' => '111222',
            'access_token' => 'old_access_token',
            'refresh_token' => 'valid_refresh_token',
            'access_token_expires_at' => now()->subMinutes(5), // Expired
        ]);

        // Mock OAuth refresh response
        Http::fake([
            'https://accounts.salla.test/oauth2/token' => Http::response([
                'access_token' => 'fresh_access_token',
                'refresh_token' => 'fresh_refresh_token',
                'expires_in' => 7200,
            ], 200),
        ]);

        // Refresh token
        $result = $this->refresher->refresh('111222', 'valid_refresh_token');

        // Verify result
        $this->assertEquals('fresh_access_token', $result['access']);
        $this->assertEquals('fresh_refresh_token', $result['refresh']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $result['expires_at']);

        // Verify token was updated in database
        $updatedToken = MerchantToken::where('salla_merchant_id', '111222')->first();
        $this->assertEquals('fresh_access_token', $updatedToken->access_token);
        $this->assertEquals('fresh_refresh_token', $updatedToken->refresh_token);
        $this->assertTrue($updatedToken->access_token_expires_at->isFuture());
    }

    /**
     * Test token store needsRefresh method
     */
    public function test_token_needs_refresh_detection(): void
    {
        $user = User::factory()->create([
            'email' => 'merchant333444@example.com',
            'name' => 'Test Store',
        ]);

        $merchant = Merchant::create([
            'store_id' => 'store-333444',
            'claimed_by_user_id' => $user->id,
            'email' => $user->email,
            'salla_merchant_id' => '333444',
            'store_name' => 'Test Store',
            'n8n_base_url' => 'https://example.com',
            'n8n_webhook_path' => '/webhook/salla',
            'is_active' => true,
            'is_approved' => true,
        ]);

        // Token expiring soon (less than 60 seconds)
        $expiringToken = MerchantToken::create([
            'merchant_id' => $merchant->id,
            'salla_merchant_id' => '333444',
            'access_token' => 'expiring_token',
            'refresh_token' => 'refresh_token',
            'access_token_expires_at' => now()->addSeconds(30),
        ]);

        $this->assertTrue($this->tokenStore->needsRefresh($expiringToken));

        $otherUser = User::factory()->create([
            'email' => 'merchant555666@example.com',
            'name' => 'Fresh Store',
        ]);

        $otherMerchant = Merchant::create([
            'store_id' => 'store-555666',
            'claimed_by_user_id' => $otherUser->id,
            'email' => $otherUser->email,
            'salla_merchant_id' => '555666',
            'store_name' => 'Fresh Store',
            'n8n_base_url' => 'https://example.com',
            'n8n_webhook_path' => '/webhook/salla',
            'is_active' => true,
            'is_approved' => true,
        ]);

        $freshToken = MerchantToken::create([
            'merchant_id' => $otherMerchant->id,
            'salla_merchant_id' => '555666',
            'access_token' => 'fresh_token',
            'refresh_token' => 'refresh_token',
            'access_token_expires_at' => now()->addHours(2),
        ]);

        $this->assertFalse($this->tokenStore->needsRefresh($freshToken));
    }

    /**
     * Test failed refresh throws exception
     */
    public function test_failed_refresh_throws_exception(): void
    {
        Http::fake([
            'https://accounts.salla.test/oauth2/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'The refresh token is invalid.',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to refresh OAuth token');

        $this->refresher->refresh('111222', 'invalid_refresh_token');
    }
}
