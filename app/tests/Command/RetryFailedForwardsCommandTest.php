<?php

use App\Models\Merchant;
use App\Models\WebhookEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use function Pest\Laravel\artisan;

it('retries failed webhook events and updates status', function () {
    Schema::dropIfExists('webhook_events');
    Schema::dropIfExists('merchants');

    Schema::create('merchants', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('store_id')->nullable();
        $table->string('email')->nullable();
        $table->string('password')->nullable();
        $table->string('salla_merchant_id')->nullable();
        $table->string('store_name')->nullable();
        $table->string('n8n_base_url')->nullable();
        $table->string('n8n_path')->nullable();
        $table->enum('n8n_auth_type', ['none', 'bearer', 'basic'])->default('none');
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
        $table->enum('status', ['stored', 'sent', 'skipped', 'failed']);
        $table->unsignedInteger('attempts')->default(0);
        $table->text('last_error')->nullable();
        $table->timestamps();
    });

    Http::fake([
        'https://retry.example.com/*' => Http::response('ok', 200),
    ]);

    $merchant = Merchant::create([
        'store_id' => 'store-1',
        'email' => 'owner@example.com',
        'password' => bcrypt('secret'),
        'salla_merchant_id' => 'merchant-1',
        'n8n_base_url' => 'https://retry.example.com',
        'is_active' => true,
    ]);

    $event = WebhookEvent::create([
        'salla_event' => 'order.created',
        'salla_event_id' => 'evt-1',
        'salla_merchant_id' => 'merchant-1',
        'headers' => [],
        'payload' => ['foo' => 'bar'],
        'status' => 'failed',
        'attempts' => 1,
        'last_error' => 'timeout',
    ]);

    artisan('webhooks:retry-failed')->assertExitCode(0);

    $event->refresh();

    expect($event->status)->toBe('sent');
    expect($event->attempts)->toBeGreaterThan(1);
    expect($event->last_error)->toBeNull();
});
