<?php

use App\Http\Controllers\Actions\OrdersController;
use App\Http\Controllers\Actions\ProductsController;
use App\Http\Controllers\Actions\CustomersController;
use App\Http\Controllers\Actions\CouponsController;
use App\Http\Controllers\Actions\CategoriesController;
use App\Http\Controllers\Actions\ExportsController;
use App\Http\Controllers\Actions\SallaProxyController;
use Illuminate\Support\Facades\Route;

Route::domain(config('panels.admin_domain'))->group(function () {
    Route::middleware(['actions.auth'])->prefix('actions')->group(function () {
        Route::post('/salla', SallaProxyController::class);

        Route::prefix('orders')->group(function () {
            Route::get('/', [OrdersController::class, 'index']);
            Route::get('/list', [OrdersController::class, 'index']);
            Route::get('/get', [OrdersController::class, 'show']);
            Route::post('/create', [OrdersController::class, 'store']);
            Route::patch('/update', [OrdersController::class, 'update']);
            Route::delete('/delete', [OrdersController::class, 'destroy']);

            Route::post('/', [OrdersController::class, 'store']);
            Route::match(['put', 'patch'], '/{id}', [OrdersController::class, 'update']);
            Route::delete('/{id}', [OrdersController::class, 'destroy']);
            Route::get('/{id}', [OrdersController::class, 'show']);
        });

        Route::get('/products', [ProductsController::class, 'index']);
        Route::get('/products/{id}', [ProductsController::class, 'show']);
        Route::post('/products', [ProductsController::class, 'store']);
        Route::put('/products/{id}', [ProductsController::class, 'update']);
        Route::delete('/products/{id}', [ProductsController::class, 'destroy']);

        Route::get('/customers', [CustomersController::class, 'index']);
        Route::get('/customers/{id}', [CustomersController::class, 'show']);
        Route::put('/customers/{id}', [CustomersController::class, 'update']);
        Route::delete('/customers/{id}', [CustomersController::class, 'destroy']);

        Route::get('/coupons', [CouponsController::class, 'index']);
        Route::get('/coupons/{id}', [CouponsController::class, 'show']);
        Route::post('/coupons', [CouponsController::class, 'store']);
        Route::put('/coupons/{id}', [CouponsController::class, 'update']);
        Route::delete('/coupons/{id}', [CouponsController::class, 'destroy']);

        Route::get('/categories', [CategoriesController::class, 'index']);
        Route::get('/categories/{id}', [CategoriesController::class, 'show']);
        Route::post('/categories', [CategoriesController::class, 'store']);
        Route::put('/categories/{id}', [CategoriesController::class, 'update']);
        Route::delete('/categories/{id}', [CategoriesController::class, 'destroy']);

        Route::get('/exports', [ExportsController::class, 'index']);
        Route::post('/exports', [ExportsController::class, 'store']);
        Route::get('/exports/{id}/status', [ExportsController::class, 'status']);
        Route::get('/exports/{id}/download', [ExportsController::class, 'download']);
    });
});
