<?php

use Illuminate\Support\Facades\Route;

Route::domain(config('panels.admin_domain'))
    ->group(function () {
        require __DIR__.'/admin.php';
    });

Route::domain(config('panels.merchant_domain'))
    ->group(function () {
        require __DIR__.'/merchant.php';
    });
