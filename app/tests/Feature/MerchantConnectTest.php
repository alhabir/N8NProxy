<?php

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withServerVariables([
        'HTTP_HOST' => config('panels.merchant_domain'),
        'SERVER_NAME' => config('panels.merchant_domain'),
    ]);
});

it('allows a merchant user to claim an authorized Salla store', function () {
    $user = User::factory()->create();

    $unclaimed = Merchant::create([
        'store_id' => 'store-claim',
        'store_name' => 'Store Waiting Claim',
        'store_domain' => 'claim-me.salla.sa',
        'email' => 'store@example.com',
        'salla_merchant_id' => 'CLAIM123',
        'salla_access_token' => 'access_token',
        'salla_refresh_token' => 'refresh_token',
        'salla_token_expires_at' => now()->addDay(),
        'connected_at' => now(),
        'is_active' => true,
        'is_approved' => true,
    ]);

    expect($unclaimed->claimed_by_user_id)->toBeNull();

    $this->actingAs($user, 'merchant')
        ->post('/settings/connect-salla/claim', [
            'store_domain' => 'claim-me.salla.sa',
        ])
        ->assertRedirect(route('settings.connect-salla'))
        ->assertSessionHas('success');

    $unclaimed->refresh();
    expect($unclaimed->claimed_by_user_id)->toBe($user->id);
});

it('rejects claim when store is not authorized', function () {
    $user = User::factory()->create();

    $unclaimed = Merchant::create([
        'store_id' => 'store-pending',
        'store_name' => 'Pending Store',
        'store_domain' => 'pending.salla.sa',
        'email' => 'pending@example.com',
        'salla_merchant_id' => 'PENDING123',
        'is_active' => true,
        'is_approved' => false,
    ]);

    $this->actingAs($user, 'merchant')
        ->post('/settings/connect-salla/claim', [
            'store_domain' => 'pending.salla.sa',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $unclaimed->refresh();
    expect($unclaimed->claimed_by_user_id)->toBeNull();
});
