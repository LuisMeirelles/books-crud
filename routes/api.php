<?php

use App\Http\Controllers\V1;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->group(function () {
        Route::post('/auth/token', [V1\AuthController::class, 'token']);
    });
