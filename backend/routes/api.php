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
use App\Http\Controllers\Api\V1\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Api\V1\Tenant\ProfileController as TenantProfileController;

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\FileController;

use App\Http\Controllers\Api\V1\Admin\ParcelController as AdminParcelController;
use App\Http\Controllers\Api\V1\Tenant\ParcelController as TenantParcelController;
use App\Http\Controllers\Api\V1\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Api\V1\Tenant\AnnouncementController as TenantAnnouncementController;
use App\Http\Controllers\Api\V1\NotificationController;

Route::prefix('v1')->group(function () {
    Route::get('/health', [HealthController::class, 'health']);

    // Auth
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/files/{file}', [FileController::class, 'show'])->name('files.show');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    });

    // Admin APIs
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
        Route::post('/admin/tenants/{tenant}/reset-password', [AdminTenantController::class, 'resetPassword']);
        Route::post('/admin/tenants/{tenant}/move-room', [AdminTenantController::class, 'moveRoom']);

        // Invoices
        Route::get('/admin/invoices', [AdminInvoiceController::class, 'index']);
        Route::get('/admin/invoices/meta', [AdminInvoiceController::class, 'meta']);
        Route::get('/admin/invoices/{invoice}', [AdminInvoiceController::class, 'show']);
        Route::get('/admin/invoices/{invoice}/pdf', [AdminInvoiceController::class, 'pdf']);
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

        // Parcels
        Route::get('/admin/parcels', [AdminParcelController::class, 'index']);
        Route::get('/admin/parcels/{parcel}', [AdminParcelController::class, 'show']);
        Route::post('/admin/parcels', [AdminParcelController::class, 'store']);

        // ✅ เปลี่ยนจาก PUT เป็น POST เพื่อให้ multipart/form-data ทำงานนิ่ง
        Route::post('/admin/parcels/{parcel}', [AdminParcelController::class, 'update']);

        Route::post('/admin/parcels/{parcel}/pickup', [AdminParcelController::class, 'pickup']);
        Route::delete('/admin/parcels/{parcel}', [AdminParcelController::class, 'destroy']);

        // Announcements
        Route::get('/admin/announcements', [AdminAnnouncementController::class, 'index']);
        Route::get('/admin/announcements/{announcement}', [AdminAnnouncementController::class, 'show']);
        Route::post('/admin/announcements', [AdminAnnouncementController::class, 'store']);

        // ✅ เปลี่ยนจาก PUT เป็น POST เพื่อให้ multipart/form-data ทำงานนิ่ง
        Route::post('/admin/announcements/{announcement}', [AdminAnnouncementController::class, 'update']);

        Route::post('/admin/announcements/{announcement}/publish', [AdminAnnouncementController::class, 'publish']);
        Route::post('/admin/announcements/{announcement}/expire', [AdminAnnouncementController::class, 'expire']);
        Route::delete('/admin/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy']);
    });

    // Tenant APIs
    Route::middleware(['auth:sanctum', 'role:tenant'])->group(function () {
        // Dashboard
        Route::get('/tenant/dashboard/summary', [TenantDashboardController::class, 'summary']);

        // Profile
        Route::get('/tenant/profile', [TenantProfileController::class, 'show']);
        Route::put('/tenant/profile', [TenantProfileController::class, 'update']);

        // Invoices
        Route::get('/tenant/invoices', [TenantInvoiceController::class, 'index']);
        Route::get('/tenant/invoices/{invoice}', [TenantInvoiceController::class, 'show']);
        Route::get('/tenant/invoices/{invoice}/pdf', [TenantInvoiceController::class, 'pdf']);

        // Repairs
        Route::get('/tenant/repairs', [TenantRepairController::class, 'index']);
        Route::post('/tenant/repairs', [TenantRepairController::class, 'store']);

        // Payments
        Route::post('/tenant/payments/{invoice}', [TenantPaymentController::class, 'store']);

        // Parcels
        Route::get('/tenant/parcels', [TenantParcelController::class, 'index']);
        Route::get('/tenant/parcels/{parcel}', [TenantParcelController::class, 'show']);

        // Announcements
        Route::get('/tenant/announcements', [TenantAnnouncementController::class, 'index']);
        Route::get('/tenant/announcements/{announcement}', [TenantAnnouncementController::class, 'show']);
        Route::post('/tenant/announcements/{announcement}/read', [TenantAnnouncementController::class, 'read']);
        Route::get('/tenant/announcements/urgent/active', [TenantAnnouncementController::class, 'urgentActive']);
    });
});