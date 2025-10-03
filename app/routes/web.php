<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SallaWebhookController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', [SallaWebhookController::class, 'health']);
Route::middleware('api')->group(function () {
    Route::post('/webhooks/salla', [SallaWebhookController::class, 'ingest']);
    Route::post('/webhooks/test', [SallaWebhookController::class, 'test']);
});
