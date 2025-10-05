<?php

use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

it('registers the admin middleware alias', function () {
    $aliases = app('router')->getMiddleware();

    expect($aliases)
        ->toHaveKey('admin')
        ->and($aliases['admin'])
        ->toBe(AdminOnly::class);
});

it('applies the admin middleware alias to admin routes', function () {
    $route = Route::getRoutes()->getByName('admin.index');

    expect($route)->not->toBeNull();

    expect($route->middleware())->toContain('admin');
});
