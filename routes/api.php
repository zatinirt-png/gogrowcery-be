<?php

use App\Http\Controllers\Api\Admin\BountyBidController as AdminBountyBidController;
use App\Http\Controllers\Api\Admin\BountyBidItemApprovalController;
use App\Http\Controllers\Api\Admin\BountyController as AdminBountyController;
use App\Http\Controllers\Api\Admin\SupplierApprovalController;
use App\Http\Controllers\Api\Admin\SupplierCreateController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Supplier\BountyBidController as SupplierBountyBidController;
use App\Http\Controllers\Api\Supplier\BountyController as SupplierBountyController;
use App\Http\Controllers\Api\SupplierRegistrationController;
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
        Route::get('/suppliers', [SupplierApprovalController::class, 'index']);
        Route::get('/suppliers/{supplierProfile}', [SupplierApprovalController::class, 'show']);
        Route::post('/suppliers/{supplierProfile}/approval', [SupplierApprovalController::class, 'approveOrReject']);
        Route::get('/suppliers/{supplierProfile}/documents/{type}', [SupplierApprovalController::class, 'downloadDocument'])->name('admin.suppliers.documents');
        Route::get('/suppliers/{supplierProfile}/photos/{type}', [SupplierApprovalController::class, 'downloadPhoto']);

        // Bounty
        Route::get('/bounties', [AdminBountyController::class, 'index']);
        Route::post('/bounties', [AdminBountyController::class, 'store']);
        Route::get('/bounties/{bounty}', [AdminBountyController::class, 'show']);
        Route::put('/bounties/{bounty}', [AdminBountyController::class, 'update']);
        Route::patch('/bounties/{bounty}/status', [AdminBountyController::class, 'updateStatus']);
        Route::patch('/bounties/{bounty}/extend-deadline', [AdminBountyController::class, 'extendDeadline']);

        Route::get('/bounties/{bounty}/bids', [AdminBountyBidController::class, 'index']);
        Route::get('/bounties/{bounty}/bids/{bountyBid}', [AdminBountyBidController::class, 'show']);

        Route::post('/bounties/{bounty}/bids/{bountyBid}/items/{bountyBidItem}/approve', [BountyBidItemApprovalController::class, 'approve']);
        Route::get('/bounties/{bounty}/bids/{bountyBid}/items/{bountyBidItem}', [BountyBidItemApprovalController::class, 'show']);
        Route::get('/bounties/{bounty}/bids/{bountyBid}/items/{bountyBidItem}/proof', [BountyBidItemApprovalController::class, 'downloadProof']);
    });

Route::prefix('supplier')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/bounties', [SupplierBountyController::class, 'index']);
        Route::get('/bounties/{bounty}', [SupplierBountyController::class, 'show']);

        Route::get('/bids', [SupplierBountyBidController::class, 'myBids']);
        Route::post('/bounties/{bounty}/bid', [SupplierBountyBidController::class, 'submitOrRevise']);
        Route::get('/bounties/{bounty}/bid', [SupplierBountyBidController::class, 'myBid']);
        Route::delete('/bounties/{bounty}/bid', [SupplierBountyBidController::class, 'withdraw']);
    });
