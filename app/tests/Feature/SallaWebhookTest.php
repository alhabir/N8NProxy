<?php

use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'salla.webhook.mode' => 'token',
        'salla.webhook.token' => 'test-token',
    ]);

    $this->withServerVariables([
        'HTTP_HOST' => config('panels.admin_domain'),
        'SERVER_NAME' => config('panels.admin_domain'),
    ]);
});

it('rejects requests missing the webhook token', function () {
    $payload = [
        'event' => 'order.created',
        'id' => 'evt-123',
        'data' => [
            'store' => ['id' => '112233'],
        ],
    ];

    $this->postJson('/webhooks/salla/app-events', $payload)
        ->assertStatus(401)
        ->assertJson(['error' => 'invalid_token']);

    expect(WebhookEvent::count())->toBe(0);
});

it('processes app.store.authorize and stores oauth tokens', function () {
    $payload = [
        'event' => 'app.store.authorize',
        'data' => [
            'store' => [
                'id' => '789456',
                'name' => 'My Salla Store',
                'domain' => 'my-store.salla.sa',
                'email' => 'owner@example.com',
            ],
            'tokens' => [
                'access_token' => 'access_token_123',
                'refresh_token' => 'refresh_token_456',
                'expires_in' => 7200,
            ],
        ],
    ];

    $response = $this->postJson('/webhooks/salla/app-events', $payload, [
        'X-Webhook-Token' => 'test-token',
    ]);

    $response->assertOk()
        ->assertJson([
            'ok' => true,
            'kind' => 'app',
            'event' => 'app.store.authorize',
        ]);

    $merchant = Merchant::where('salla_merchant_id', '789456')->firstOrFail();
    expect($merchant->store_name)->toBe('My Salla Store');
    expect($merchant->store_domain)->toBe('my-store.salla.sa');
    expect($merchant->salla_access_token)->toBe('access_token_123');
    expect($merchant->salla_refresh_token)->toBe('refresh_token_456');
    expect($merchant->salla_token_expires_at)->not->toBeNull();
    expect($merchant->connected_at)->not->toBeNull();
    expect($merchant->is_approved)->toBeTrue();

    $token = MerchantToken::where('salla_merchant_id', '789456')->firstOrFail();
    expect($token->access_token)->toBe('access_token_123');
    expect($token->refresh_token)->toBe('refresh_token_456');
});

it('stores a store event and forwards it to n8n', function () {
    $merchant = Merchant::create([
        'store_id' => 'store-112233',
        'email' => 'merchant112233@example.com',
        'salla_merchant_id' => '112233',
        'store_name' => 'Connected Store',
        'salla_access_token' => 'access_token',
        'salla_refresh_token' => 'refresh_token',
        'salla_token_expires_at' => now()->addHour(),
        'n8n_base_url' => 'https://example.com',
        'n8n_webhook_path' => '/webhook/salla',
        'is_active' => true,
        'is_approved' => true,
        'connected_at' => now(),
    ]);

    Http::fake([
        'https://example.com/*' => Http::response('ok', 200),
    ]);

    $payload = [
        'event' => 'order.created',
        'id' => 'evt-forward',
        'data' => [
            'store' => ['id' => '112233'],
            'order' => ['id' => 12345],
        ],
    ];

    $response = $this->call(
        'POST',
        '/webhooks/salla/app-events',
        [],
        [],
        [],
        [
            'HTTP_X_WEBHOOK_TOKEN' => 'test-token',
            'HTTP_X_SALLA_EVENT' => 'order.created',
            'HTTP_X_SALLA_EVENT_ID' => 'evt-forward',
            'HTTP_X_SALLA_MERCHANT_ID' => '112233',
            'CONTENT_TYPE' => 'application/json',
        ],
        json_encode($payload)
    );

    $response->assertOk()
        ->assertJson([
            'ok' => true,
            'kind' => 'store',
            'forwarded' => true,
        ]);

    $event = WebhookEvent::first();
    expect($event)->not->toBeNull();
    expect($event->status)->toBe('sent');
    expect($event->response_status)->toBe(200);
    expect($event->salla_event_id)->toBe('evt-forward');
});

it('returns 202 when store event merchant is missing', function () {
    Http::fake();

    $payload = [
        'event' => 'order.created',
        'id' => 'missing-merchant',
        'data' => [
            'store' => ['id' => 'missing'],
        ],
    ];

    $response = $this->postJson('/webhooks/salla/app-events', $payload, [
        'X-Webhook-Token' => 'test-token',
    ]);

    $response->assertStatus(202)
        ->assertJson([
            'ignored' => true,
            'reason' => 'merchant-not-found',
        ]);

    expect(WebhookEvent::count())->toBe(0);
});

it('treats duplicate store events as idempotent', function () {
    Merchant::create([
        'store_id' => 'store-dup',
        'email' => 'dup@example.com',
        'salla_merchant_id' => 'dup-merchant',
        'store_name' => 'Dup Store',
        'salla_access_token' => 'access',
        'salla_refresh_token' => 'refresh',
        'salla_token_expires_at' => now()->addHour(),
        'n8n_base_url' => 'https://dup.example.com',
        'n8n_webhook_path' => '/webhook/salla',
        'is_active' => true,
        'is_approved' => true,
        'connected_at' => now(),
    ]);

    Http::fake([
        'https://dup.example.com/*' => Http::response('ok', 200),
    ]);

    $payload = [
        'event' => 'order.created',
        'id' => 'dup-event',
        'data' => [
            'store' => ['id' => 'dup-merchant'],
        ],
    ];

    $headers = [
        'HTTP_X_WEBHOOK_TOKEN' => 'test-token',
        'HTTP_X_SALLA_EVENT' => 'order.created',
        'HTTP_X_SALLA_EVENT_ID' => 'dup-event',
        'HTTP_X_SALLA_MERCHANT_ID' => 'dup-merchant',
        'CONTENT_TYPE' => 'application/json',
    ];

    $this->call('POST', '/webhooks/salla/app-events', [], [], [], $headers, json_encode($payload))
        ->assertOk();

    $response = $this->call('POST', '/webhooks/salla/app-events', [], [], [], $headers, json_encode($payload));

    $response->assertOk()
        ->assertJson([
            'duplicate' => true,
            'forwarded' => true,
        ]);

    expect(WebhookEvent::count())->toBe(1);
});
