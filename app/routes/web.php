<?php

use App\Http\Controllers\SallaWebhookController;
use App\Http\Controllers\AppEventsController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/merchant', [DocsController::class, 'merchant']);
Route::get('/docs/admin', [DocsController::class, 'admin']);

// Webhook endpoints
Route::post('/webhooks/salla', [SallaWebhookController::class, 'ingest']);
Route::post('/app-events/authorized', [AppEventsController::class, 'authorized']);

// Auth routes (Breeze)
require __DIR__.'/auth.php';

// Merchant routes (authenticated)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [MerchantController::class, 'dashboard'])->name('dashboard');
    Route::get('/settings/n8n', [MerchantController::class, 'n8nSettings'])->name('settings.n8n');
    Route::post('/settings/n8n', [MerchantController::class, 'updateN8nSettings']);
    Route::post('/tests/send-webhook', [MerchantController::class, 'sendTestWebhook']);
    Route::get('/webhooks', [MerchantController::class, 'webhooks'])->name('webhooks');
    Route::get('/actions-audit', [MerchantController::class, 'actionsAudit'])->name('actions-audit');
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/merchants', [AdminController::class, 'merchants'])->name('merchants');
    Route::post('/merchants/{merchant}/approve', [AdminController::class, 'approveMerchant']);
    Route::get('/app-settings', [AdminController::class, 'appSettings'])->name('app-settings');
    Route::post('/app-settings', [AdminController::class, 'updateAppSettings']);
    Route::get('/webhooks', [AdminController::class, 'webhooks'])->name('webhooks');
    Route::get('/actions-audit', [AdminController::class, 'actionsAudit'])->name('actions-audit');
    Route::post('/tests/send-webhook/{merchant}', [AdminController::class, 'sendTestWebhook']);
});