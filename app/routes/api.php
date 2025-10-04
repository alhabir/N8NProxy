<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Actions\OrdersController;
use App\Http\Controllers\Actions\ProductsController;
use App\Http\Controllers\Actions\CustomersController;
use App\Http\Controllers\Actions\CouponsController;
use App\Http\Controllers\Actions\CategoriesController;
use App\Http\Controllers\Actions\ExportsController;
use App\Http\Controllers\SallaAppEventsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Actions API - Protected with Bearer token
Route::prefix('actions')->middleware(['actions.auth', 'throttle:60,1'])->group(function () {
    
    // Orders
    Route::post('orders/create', [OrdersController::class, 'create']);
    Route::delete('orders/delete', [OrdersController::class, 'delete']);
    Route::get('orders/get', [OrdersController::class, 'get']);
    Route::get('orders/list', [OrdersController::class, 'list']);
    Route::patch('orders/update', [OrdersController::class, 'update']);

    // Products
    Route::post('products/create', [ProductsController::class, 'create']);
    Route::delete('products/delete', [ProductsController::class, 'delete']);
    Route::get('products/get', [ProductsController::class, 'get']);
    Route::get('products/list', [ProductsController::class, 'list']);
    Route::patch('products/update', [ProductsController::class, 'update']);

    // Customers
    Route::delete('customers/delete', [CustomersController::class, 'delete']);
    Route::get('customers/get', [CustomersController::class, 'get']);
    Route::get('customers/list', [CustomersController::class, 'list']);
    Route::patch('customers/update', [CustomersController::class, 'update']);

    // Marketing / Coupons
    Route::post('marketing/coupons/create', [CouponsController::class, 'create']);
    Route::delete('marketing/coupons/delete', [CouponsController::class, 'delete']);
    Route::get('marketing/coupons/get', [CouponsController::class, 'get']);
    Route::get('marketing/coupons/list', [CouponsController::class, 'list']);
    Route::patch('marketing/coupons/update', [CouponsController::class, 'update']);

    // Categories
    Route::post('categories/create', [CategoriesController::class, 'create']);
    Route::delete('categories/delete', [CategoriesController::class, 'delete']);
    Route::get('categories/get', [CategoriesController::class, 'get']);
    Route::get('categories/list', [CategoriesController::class, 'list']);
    Route::patch('categories/update', [CategoriesController::class, 'update']);

    // Exports
    Route::post('exports/create', [ExportsController::class, 'create']);
    Route::get('exports/list', [ExportsController::class, 'list']);
    Route::get('exports/status', [ExportsController::class, 'status']);
    Route::get('exports/download', [ExportsController::class, 'download']);
});

// Salla App Events (Easy Mode OAuth) → capture tokens
Route::post('/app-events/authorized', [SallaAppEventsController::class, 'authorized']);
Route::post('/app-events/installed', [SallaAppEventsController::class, 'installed']);