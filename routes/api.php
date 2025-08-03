<?php

use App\Http\Controllers\V1;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->group(function () {
        Route::post('/auth/token', [V1\AuthController::class, 'token']);

        Route::middleware('auth:sanctum')
            ->group(function () {
                Route::apiResource('livros', V1\BookController::class)
                    ->only(['index', 'store']);
            });
    });
