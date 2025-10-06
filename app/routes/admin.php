<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\SallaAppEventsController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\SallaWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/admin', [DocsController::class, 'admin']);

Route::post('/webhooks/salla', [SallaWebhookController::class, 'ingest']);
Route::post('/app-events/authorized', [SallaAppEventsController::class, 'authorized']);
Route::post('/app-events/installed', [SallaAppEventsController::class, 'installed']);

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/merchants', [AdminController::class, 'merchants'])->name('merchants');
        Route::post('/merchants/{merchant}/approve', [AdminController::class, 'approveMerchant'])->name('merchants.approve');
        Route::get('/app-settings', [AdminController::class, 'appSettings'])->name('app-settings');
        Route::post('/app-settings', [AdminController::class, 'appSettingsSave']);
        Route::get('/webhooks', [AdminController::class, 'webhooks'])->name('webhooks');
        Route::get('/actions-audit', [AdminController::class, 'actionsAudit'])->name('actions-audit');
        Route::post('/tests/send-webhook/{merchant}', [AdminController::class, 'sendTestWebhook'])->name('tests.send-webhook');
    });
