<?php

use App\Http\Controllers\SallaWebhookController;
use App\Http\Middleware\VerifySallaWebhookToken;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/salla/app-events', [SallaWebhookController::class, 'handle'])
    ->middleware(VerifySallaWebhookToken::class);
