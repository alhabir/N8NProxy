<?php

use App\Http\Controllers\DocsController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/merchant', [DocsController::class, 'merchant']);

require __DIR__.'/auth.php';

Route::middleware(['auth:merchant', 'verified'])->group(function () {
    Route::get('/dashboard', [MerchantController::class, 'dashboard'])->name('dashboard');
    Route::get('/settings/n8n', [MerchantController::class, 'n8nSettings'])->name('settings.n8n');
    Route::post('/settings/n8n', [MerchantController::class, 'updateN8nSettings']);
    Route::post('/tests/send-webhook', [MerchantController::class, 'sendTestWebhook'])->name('tests.send-webhook');
    Route::get('/webhooks', [MerchantController::class, 'webhooks'])->name('webhooks');
    Route::get('/actions-audit', [MerchantController::class, 'actionsAudit'])->name('actions-audit');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
