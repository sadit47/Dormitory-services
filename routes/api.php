<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Api\V1\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Api\V1\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Api\V1\Admin\RepairController as AdminRepairController;
use App\Http\Controllers\Api\V1\Admin\PaymentReviewController as AdminPaymentReviewController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Api\V1\Tenant\InvoiceController as TenantInvoiceController;
use App\Http\Controllers\Api\V1\Tenant\RepairController as TenantRepairController;
use App\Http\Controllers\Api\V1\Tenant\PaymentController as TenantPaymentController;

Route::prefix('v1')->group(function () {

    // Auth
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });

    // Admin example: Rooms CRUD
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // Dashboard
        Route::get('/admin/dashboard/summary', [AdminDashboardController::class, 'summary']);

        // Profile
        Route::get('/admin/profile', [AdminProfileController::class, 'show']);
        Route::put('/admin/profile', [AdminProfileController::class, 'update']);

        // Rooms
        Route::get('/admin/rooms/meta', [AdminRoomController::class, 'meta']);
        Route::get('/admin/rooms', [AdminRoomController::class, 'index']);
        Route::get('/admin/rooms/{room}', [AdminRoomController::class, 'show']);
        Route::post('/admin/rooms', [AdminRoomController::class, 'store']);
        Route::put('/admin/rooms/{room}', [AdminRoomController::class, 'update']);
        Route::delete('/admin/rooms/{room}', [AdminRoomController::class, 'destroy']);
        Route::get('/admin/rooms/{room}/tenant', [AdminRoomController::class, 'tenant']);

        // Tenants
        Route::get('/admin/tenants/meta', [AdminTenantController::class, 'meta']);
        Route::get('/admin/tenants', [AdminTenantController::class, 'index']);
        Route::get('/admin/tenants/{tenant}', [AdminTenantController::class, 'show']);
        Route::post('/admin/tenants', [AdminTenantController::class, 'store']);
        Route::put('/admin/tenants/{tenant}', [AdminTenantController::class, 'update']);
        Route::delete('/admin/tenants/{tenant}', [AdminTenantController::class, 'destroy']);

        // Invoices
        Route::get('/admin/invoices', [AdminInvoiceController::class, 'index']);
        Route::get('/admin/invoices/meta', [AdminInvoiceController::class, 'meta']);
        Route::get('/admin/invoices/{invoice}', [AdminInvoiceController::class, 'show']);
        Route::post('/admin/invoices', [AdminInvoiceController::class, 'store']);
        Route::put('/admin/invoices/{invoice}', [AdminInvoiceController::class, 'update']);
        Route::delete('/admin/invoices/{invoice}', [AdminInvoiceController::class, 'destroy']);

        // Repairs
        Route::get('/admin/repairs', [AdminRepairController::class, 'index']);
        Route::patch('/admin/repairs/{repair}/status', [AdminRepairController::class, 'updateStatus']);
        Route::delete('/admin/repairs/{repair}', [AdminRepairController::class, 'destroy']);

        // Payments review
        Route::get('/admin/payments/pending', [AdminPaymentReviewController::class, 'pending']);
        Route::post('/admin/payments/{payment}/approve', [AdminPaymentReviewController::class, 'approve']);
        Route::post('/admin/payments/{payment}/reject', [AdminPaymentReviewController::class, 'reject']);
    });

    // Tenant APIs
    Route::middleware(['auth:sanctum', 'role:tenant'])->group(function () {
        Route::get('/tenant/invoices', [TenantInvoiceController::class, 'index']);
        Route::get('/tenant/invoices/{invoice}', [TenantInvoiceController::class, 'show']);

        Route::get('/tenant/repairs', [TenantRepairController::class, 'index']);
        Route::post('/tenant/repairs', [TenantRepairController::class, 'store']);

        Route::post('/tenant/payments/{invoice}', [TenantPaymentController::class, 'store']);
    });

});
