<?php

use App\Http\Controllers\Api\Admin\SupplierApprovalController;
use App\Http\Controllers\Api\Admin\SupplierCreateController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SupplierRegistrationController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::prefix('auth')->group(function () {
    // Register
    Route::post('/register/buyer', [AuthController::class, 'registerBuyer']);
    Route::post('/register/supplier', [SupplierRegistrationController::class, 'register']);

    // Login
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {

     // Supplier create by admin
        Route::post('/suppliers', [SupplierCreateController::class, 'store']);

        // Supplier approval
        Route::get('/suppliers/pending',          [SupplierApprovalController::class, 'index']);
        Route::get('/suppliers/{supplierProfile}', [SupplierApprovalController::class, 'show']);
        Route::patch('/suppliers/{supplierProfile}/approve', [SupplierApprovalController::class, 'approve']);
        Route::patch('/suppliers/{supplierProfile}/reject',  [SupplierApprovalController::class, 'reject']);
    });
