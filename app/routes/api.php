<?php

use App\Http\Controllers\Actions\CategoriesController;
use App\Http\Controllers\Actions\CouponsController;
use App\Http\Controllers\Actions\CustomersController;
use App\Http\Controllers\Actions\ExportsController;
use App\Http\Controllers\Actions\OrdersController;
use App\Http\Controllers\Actions\ProductsController;
use App\Http\Controllers\SallaAppEventsController;
use App\Http\Controllers\SallaWebhookController;
use App\Http\Middleware\ActionsApiAuth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Salla Webhooks (existing functionality)
Route::post('/webhook/salla', [SallaWebhookController::class, 'handle'])
    ->name('webhook.salla');

// Salla App Events (Easy Mode OAuth) - capture tokens
Route::post('/app-events/authorized', [SallaAppEventsController::class, 'authorized'])
    ->name('app-events.authorized');
Route::post('/app-events/installed', [SallaAppEventsController::class, 'installed'])
    ->name('app-events.installed');

// Actions API - Protected by Bearer token
Route::prefix('actions')->middleware([ActionsApiAuth::class])->group(function () {
    // Orders
    Route::post('orders/create', [OrdersController::class, 'create'])
        ->name('actions.orders.create');
    Route::delete('orders/delete', [OrdersController::class, 'delete'])
        ->name('actions.orders.delete');
    Route::get('orders/get', [OrdersController::class, 'get'])
        ->name('actions.orders.get');
    Route::get('orders/list', [OrdersController::class, 'list'])
        ->name('actions.orders.list');
    Route::patch('orders/update', [OrdersController::class, 'update'])
        ->name('actions.orders.update');

    // Products
    Route::post('products/create', [ProductsController::class, 'create'])
        ->name('actions.products.create');
    Route::delete('products/delete', [ProductsController::class, 'delete'])
        ->name('actions.products.delete');
    Route::get('products/get', [ProductsController::class, 'get'])
        ->name('actions.products.get');
    Route::get('products/list', [ProductsController::class, 'list'])
        ->name('actions.products.list');
    Route::patch('products/update', [ProductsController::class, 'update'])
        ->name('actions.products.update');

    // Customers
    Route::delete('customers/delete', [CustomersController::class, 'delete'])
        ->name('actions.customers.delete');
    Route::get('customers/get', [CustomersController::class, 'get'])
        ->name('actions.customers.get');
    Route::get('customers/list', [CustomersController::class, 'list'])
        ->name('actions.customers.list');
    Route::patch('customers/update', [CustomersController::class, 'update'])
        ->name('actions.customers.update');

    // Marketing / Coupons
    Route::post('marketing/coupons/create', [CouponsController::class, 'create'])
        ->name('actions.coupons.create');
    Route::delete('marketing/coupons/delete', [CouponsController::class, 'delete'])
        ->name('actions.coupons.delete');
    Route::get('marketing/coupons/get', [CouponsController::class, 'get'])
        ->name('actions.coupons.get');
    Route::get('marketing/coupons/list', [CouponsController::class, 'list'])
        ->name('actions.coupons.list');
    Route::patch('marketing/coupons/update', [CouponsController::class, 'update'])
        ->name('actions.coupons.update');

    // Categories
    Route::post('categories/create', [CategoriesController::class, 'create'])
        ->name('actions.categories.create');
    Route::delete('categories/delete', [CategoriesController::class, 'delete'])
        ->name('actions.categories.delete');
    Route::get('categories/get', [CategoriesController::class, 'get'])
        ->name('actions.categories.get');
    Route::get('categories/list', [CategoriesController::class, 'list'])
        ->name('actions.categories.list');
    Route::patch('categories/update', [CategoriesController::class, 'update'])
        ->name('actions.categories.update');

    // Exports
    Route::post('exports/create', [ExportsController::class, 'create'])
        ->name('actions.exports.create');
    Route::get('exports/list', [ExportsController::class, 'list'])
        ->name('actions.exports.list');
    Route::get('exports/status', [ExportsController::class, 'status'])
        ->name('actions.exports.status');
    Route::get('exports/download', [ExportsController::class, 'download'])
        ->name('actions.exports.download');
});