<?php

use App\Models\Merchant;
use App\Models\WebhookEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\WithoutRefreshDatabaseTestCase;

const TEST_SALLA_SECRET = 'test-secret-key';

beforeEach(function () {
    config([
        'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
        'salla.webhook_secret' => TEST_SALLA_SECRET,
    ]);

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Schema::dropIfExists('forwarding_attempts');
    Schema::dropIfExists('webhook_events');
    Schema::dropIfExists('merchants');

    Schema::create('merchants', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('store_id')->unique()->nullable();
        $table->string('email')->unique();
        $table->string('password');
        $table->string('salla_merchant_id')->nullable();
        $table->string('store_name')->nullable();
        $table->string('n8n_base_url')->nullable();
        $table->string('n8n_path')->nullable();
        $table->string('n8n_auth_type')->default('none');
        $table->text('n8n_bearer_token')->nullable();
        $table->string('n8n_basic_user')->nullable();
        $table->text('n8n_basic_pass')->nullable();
        $table->boolean('is_active')->default(true);
        $table->boolean('is_approved')->default(false);
        $table->timestamp('last_ping_ok_at')->nullable();
        $table->timestamps();
    });

    Schema::create('webhook_events', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('salla_event');
        $table->string('salla_event_id')->unique();
        $table->string('salla_merchant_id');
        $table->json('headers');
        $table->json('payload');
        $table->string('status');
        $table->unsignedInteger('attempts')->default(0);
        $table->text('last_error')->nullable();
        $table->timestamps();
    });

    Schema::create('forwarding_attempts', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('webhook_event_id');
        $table->string('target_url');
        $table->unsignedSmallInteger('response_status')->nullable();
        $table->text('response_body')->nullable();
        $table->unsignedInteger('duration_ms');
        $table->timestamps();

        $table->foreign('webhook_event_id')->references('id')->on('webhook_events')->cascadeOnDelete();
    });
});

function buildPayload(string $merchantId = '112233'): array
{
    return [
        'event' => 'order.created',
        'id' => 'evt-123',
        'data' => [
            'store' => [
                'id' => $merchantId,
            ],
        ],
    ];
}

function signRaw(string $raw): string
{
    return base64_encode(hash_hmac('sha256', $raw, TEST_SALLA_SECRET, true));
}

it('accepts and processes a webhook with a valid signature', function () {
    $merchant = Merchant::create([
        'email' => 'merchant@example.com',
        'password' => bcrypt('secret'),
        'salla_merchant_id' => '112233',
        'n8n_base_url' => 'https://example.com',
        'is_active' => true,
        'is_approved' => true,
    ]);

    Http::fake([
        'https://example.com/*' => Http::response('ok', 200),
    ]);

    $payload = buildPayload($merchant->salla_merchant_id);
    $raw = json_encode($payload);
    $signature = signRaw($raw);

    $response = $this->call('POST', '/webhooks/salla', [], [], [], [
        'HTTP_X_SALLA_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);

    $response->assertOk();
    $response->assertSeeText('OK');

    expect(WebhookEvent::count())->toBe(1);

    $event = WebhookEvent::first();
    expect($event->salla_event)->toBe('order.created');
    expect($event->salla_merchant_id)->toBe('112233');
    expect($event->status)->toBe('sent');
});

it('rejects a webhook with an invalid signature', function () {
    $payload = buildPayload('445566');
    $raw = json_encode($payload);

    $response = $this->call('POST', '/webhooks/salla', [], [], [], [
        'HTTP_X_SALLA_SIGNATURE' => 'invalid-signature',
        'CONTENT_TYPE' => 'application/json',
    ], $raw);

    $response->assertStatus(401);
    $response->assertJson([
        'error' => 'invalid_signature',
        'reason' => 'mismatch',
    ]);

    expect(WebhookEvent::count())->toBe(0);
});
