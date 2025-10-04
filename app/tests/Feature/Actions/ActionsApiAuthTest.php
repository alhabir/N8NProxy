<?php

namespace Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionsApiAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that actions endpoints require authentication
     */
    public function test_actions_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/actions/orders/list?merchant_id=123');
        
        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Missing Bearer token']);
    }

    /**
     * Test that invalid token is rejected
     */
    public function test_invalid_token_is_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->getJson('/api/actions/orders/list?merchant_id=123');
        
        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized - Invalid token']);
    }

    /**
     * Test that valid token is accepted
     */
    public function test_valid_token_is_accepted(): void
    {
        // Set the test token in config
        config(['app.actions_token' => 'test_token_123']);
        
        $response = $this->withHeader('Authorization', 'Bearer test_token_123')
            ->getJson('/api/actions/orders/list');
        
        // Should fail with validation error (missing merchant_id) rather than auth error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['merchant_id']);
    }
}