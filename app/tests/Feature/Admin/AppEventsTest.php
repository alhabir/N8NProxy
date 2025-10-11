<?php

use App\Models\AppEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAdminUser(): User
{
    return User::factory()->create([
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);
}

it('renders the app events logs page with recent events', function () {
    $admin = createAdminUser();

    AppEvent::create([
        'event_name' => 'app.installed',
        'salla_merchant_id' => 'merchant-001',
        'payload' => ['event' => 'app.installed'],
        'event_created_at' => now()->subDay(),
    ]);

    AppEvent::create([
        'event_name' => 'app.uninstalled',
        'salla_merchant_id' => 'merchant-002',
        'payload' => ['event' => 'app.uninstalled'],
        'event_created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.app-events.index'));

    $response->assertOk();
    $response->assertSee('App Events Logs', false);
    $response->assertSee('app.installed', false);
    $response->assertSee('app.uninstalled', false);
    $response->assertSee('merchant-001', false);
    $response->assertSee('merchant-002', false);
});

it('displays install and uninstall counters on the admin dashboard', function () {
    $admin = createAdminUser();

    AppEvent::create([
        'event_name' => 'app.installed',
        'salla_merchant_id' => 'merchant-100',
        'payload' => ['event' => 'app.installed'],
    ]);

    AppEvent::create([
        'event_name' => 'app.uninstalled',
        'salla_merchant_id' => 'merchant-101',
        'payload' => ['event' => 'app.uninstalled'],
    ]);

    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.index'));

    $response->assertOk();
    $response->assertSee('App Installs', false);
    $response->assertSee('App Uninstalls', false);
    $response->assertSee('1', false);
});
