<?php

use App\Http\Controllers\Admin\AppEventsController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::guard('admin')->check()
        ? redirect()->route('admin.index')
        : redirect()->route('admin.login');
})->name('admin.landing');

Route::get('/docs/admin', [DocsController::class, 'admin']);

Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth:admin', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/merchants', [AdminController::class, 'merchants'])->name('merchants');
    Route::post('/merchants/{merchant}/approve', [AdminController::class, 'approveMerchant'])->name('merchants.approve');
    Route::delete('/merchants/{merchant}', [AdminController::class, 'destroyMerchant'])->name('merchants.destroy');
    Route::get('/app-settings', [AdminController::class, 'appSettings'])->name('app-settings');
    Route::post('/app-settings', [AdminController::class, 'appSettingsSave'])->name('app-settings.update');
    Route::get('/webhooks', [AdminController::class, 'webhooks'])->name('webhooks');
    Route::get('/actions-audit', [AdminController::class, 'actionsAudit'])->name('actions-audit');
    Route::get('/app-events', [AppEventsController::class, 'index'])->name('app-events.index');
    Route::post('/tests/send-webhook/{merchant}', [AdminController::class, 'sendTestWebhook'])->name('tests.send-webhook');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:admin')
    ->name('admin.logout');
