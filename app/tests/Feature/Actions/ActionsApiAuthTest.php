<?php

namespace Tests\Feature\Actions;

use Tests\TestCase;

class ActionsApiAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables([
            'HTTP_HOST' => config('panels.admin_domain'),
            'SERVER_NAME' => config('panels.admin_domain'),
        ]);
    }

    public function test_actions_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/actions/orders?merchant_id=123');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Missing or invalid authorization header']);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->getJson('/api/actions/orders?merchant_id=123');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid API token']);
    }

    public function test_valid_token_allows_request_to_proceed(): void
    {
        config(['app.actions_api_bearer' => 'test_token_123']);

        $mockClient = \Mockery::mock(\App\Services\Salla\SallaHttpClient::class);
        $mockClient->shouldReceive('makeRequest')
            ->once()
            ->withArgs(function ($merchantId, $method, $endpoint, $payload = []) {
                $this->assertEquals('123', $merchantId);
                $this->assertEquals('get', $method);
                $this->assertIsString($endpoint);
                $this->assertIsArray($payload);

                return true;
            })
            ->andReturn([
                'success' => true,
                'status' => 200,
                'data' => ['message' => 'ok'],
                'headers' => [],
            ]);

        app()->instance(\App\Services\Salla\SallaHttpClient::class, $mockClient);

        $response = $this->withHeader('Authorization', 'Bearer test_token_123')
            ->getJson('/api/actions/orders?merchant_id=123');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 200,
                'data' => ['message' => 'ok'],
            ]);
    }
}
