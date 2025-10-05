<?php

use App\Models\Merchant;
use App\Models\WebhookEvent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

function sign(string $raw): string {
    return base64_encode(hash_hmac('sha256', $raw, env('SALLA_WEBHOOK_SECRET', ''), true));
}

it('stores and forwards happy path order.created', function () {
    $merchant = Merchant::create([
        'store_id' => 'store-112233',
        'email' => 'merchant112233@example.com',
        'password' => bcrypt('secret'),
        'salla_merchant_id' => '112233',
        'n8n_base_url' => 'https://example.com',
        'is_active' => true,
    ]);

    $raw = file_get_contents(base_path('tests/Fixtures/salla/order.created.json'));
    $sig = sign($raw);

    Http::fake([
        'https://example.com/*' => Http::response('ok', 200),
    ]);

    $res = $this->call('POST', '/webhooks/salla', [], [], [], [
        'HTTP_X_SALLA_SIGNATURE' => $sig,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);

    $res->assertOk();
    $res->assertJson(['status' => 'sent']);
    expect(WebhookEvent::count())->toBe(1);
});

it('rejects invalid signature without storing event', function () {
    $raw = file_get_contents(base_path('tests/Fixtures/salla/customer.created.json'));
    $res = $this->call('POST', '/webhooks/salla', [], [], [], [
        'HTTP_X_SALLA_SIGNATURE' => 'bad',
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
    $res->assertStatus(401);
    $res->assertJson([
        'error' => 'invalid_signature',
        'reason' => 'mismatch',
    ]);
    expect(WebhookEvent::count())->toBe(0);
});

it('idempotent by salla_event_id', function () {
    $raw = file_get_contents(base_path('tests/Fixtures/salla/customer.created.json'));
    $sig = sign($raw);
    $payload = json_decode($raw, true);

    $this->call('POST', '/webhooks/salla', [], [], [], [
        'HTTP_X_SALLA_SIGNATURE' => $sig,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
    $res = $this->call('POST', '/webhooks/salla', [], [], [], [
        'HTTP_X_SALLA_SIGNATURE' => $sig,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);

    $res->assertOk();
    $res->assertJson(['duplicate' => true]);
    expect(WebhookEvent::count())->toBe(1);
});


