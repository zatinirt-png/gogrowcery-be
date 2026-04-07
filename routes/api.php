<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use App\Http\Controllers\Api\AuthController;

// Auth routes
Route::prefix('auth')->group(function () {

    // Register
    Route::post('/register/buyer',    [AuthController::class, 'registerBuyer']);
    Route::post('/register/supplier', [AuthController::class, 'registerSupplier']);

    // Login
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});
