<?php

use App\Http\Controllers\Actions\OrdersController;
use App\Http\Controllers\Actions\ProductsController;
use App\Http\Controllers\Actions\CustomersController;
use App\Http\Controllers\Actions\CouponsController;
use App\Http\Controllers\Actions\CategoriesController;
use App\Http\Controllers\Actions\ExportsController;
use Illuminate\Support\Facades\Route;

// Actions API routes (protected by bearer token)
Route::middleware(['actions.auth'])->prefix('actions')->group(function () {
    // Orders
    Route::get('/orders', [OrdersController::class, 'index']);
    Route::get('/orders/{id}', [OrdersController::class, 'show']);
    Route::post('/orders', [OrdersController::class, 'store']);
    Route::put('/orders/{id}', [OrdersController::class, 'update']);
    Route::delete('/orders/{id}', [OrdersController::class, 'destroy']);

    // Products
    Route::get('/products', [ProductsController::class, 'index']);
    Route::get('/products/{id}', [ProductsController::class, 'show']);
    Route::post('/products', [ProductsController::class, 'store']);
    Route::put('/products/{id}', [ProductsController::class, 'update']);
    Route::delete('/products/{id}', [ProductsController::class, 'destroy']);

    // Customers
    Route::get('/customers', [CustomersController::class, 'index']);
    Route::get('/customers/{id}', [CustomersController::class, 'show']);
    Route::put('/customers/{id}', [CustomersController::class, 'update']);
    Route::delete('/customers/{id}', [CustomersController::class, 'destroy']);

    // Coupons
    Route::get('/coupons', [CouponsController::class, 'index']);
    Route::get('/coupons/{id}', [CouponsController::class, 'show']);
    Route::post('/coupons', [CouponsController::class, 'store']);
    Route::put('/coupons/{id}', [CouponsController::class, 'update']);
    Route::delete('/coupons/{id}', [CouponsController::class, 'destroy']);

    // Categories
    Route::get('/categories', [CategoriesController::class, 'index']);
    Route::get('/categories/{id}', [CategoriesController::class, 'show']);
    Route::post('/categories', [CategoriesController::class, 'store']);
    Route::put('/categories/{id}', [CategoriesController::class, 'update']);
    Route::delete('/categories/{id}', [CategoriesController::class, 'destroy']);

    // Exports
    Route::get('/exports', [ExportsController::class, 'index']);
    Route::post('/exports', [ExportsController::class, 'store']);
    Route::get('/exports/{id}/status', [ExportsController::class, 'status']);
    Route::get('/exports/{id}/download', [ExportsController::class, 'download']);
});