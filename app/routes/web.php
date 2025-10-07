<?php

use Illuminate\Support\Facades\Route;

Route::domain(config('panels.admin_domain'))
    ->middleware(['web'])
    ->group(function () {
        require __DIR__.'/admin.php';
    });

Route::domain(config('panels.merchant_domain'))
    ->middleware(['web'])
    ->group(function () {
        require __DIR__.'/merchant.php';
    });
