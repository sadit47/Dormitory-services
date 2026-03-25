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
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/files/{file}', [FileController::class, 'show'])
            ->name('files.show')
            ->middleware('throttle:60,1');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->middleware('throttle:30,1');

        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
            ->middleware('throttle:30,1');

        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
            ->middleware('throttle:10,1');
    });

    // Admin APIs
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // Dashboard
        Route::get('/admin/dashboard/summary', [AdminDashboardController::class, 'summary'])
            ->middleware('throttle:30,1');

        // Profile
        Route::get('/admin/profile', [AdminProfileController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::put('/admin/profile', [AdminProfileController::class, 'update'])
            ->middleware('throttle:10,1');

        // Rooms
        Route::get('/admin/rooms/meta', [AdminRoomController::class, 'meta'])
            ->middleware('throttle:30,1');
        Route::get('/admin/rooms', [AdminRoomController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/admin/rooms/{room}', [AdminRoomController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::post('/admin/rooms', [AdminRoomController::class, 'store'])
            ->middleware('throttle:admin-actions');
        Route::put('/admin/rooms/{room}', [AdminRoomController::class, 'update'])
            ->middleware('throttle:admin-actions');
        Route::delete('/admin/rooms/{room}', [AdminRoomController::class, 'destroy'])
            ->middleware('throttle:admin-actions');
        Route::get('/admin/rooms/{room}/tenant', [AdminRoomController::class, 'tenant'])
            ->middleware('throttle:30,1');

        // Tenants
        Route::get('/admin/tenants/meta', [AdminTenantController::class, 'meta'])
            ->middleware('throttle:30,1');
        Route::get('/admin/tenants', [AdminTenantController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/admin/tenants/{tenant}', [AdminTenantController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::post('/admin/tenants', [AdminTenantController::class, 'store'])
            ->middleware('throttle:admin-actions');
        Route::put('/admin/tenants/{tenant}', [AdminTenantController::class, 'update'])
            ->middleware('throttle:admin-actions');
        Route::delete('/admin/tenants/{tenant}', [AdminTenantController::class, 'destroy'])
            ->middleware('throttle:admin-actions');
        Route::post('/admin/tenants/{tenant}/reset-password', [AdminTenantController::class, 'resetPassword'])
            ->middleware('throttle:5,1');
        Route::post('/admin/tenants/{tenant}/move-room', [AdminTenantController::class, 'moveRoom'])
            ->middleware('throttle:admin-actions');

        // Invoices
        Route::get('/admin/invoices', [AdminInvoiceController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/admin/invoices/meta', [AdminInvoiceController::class, 'meta'])
            ->middleware('throttle:30,1');
        Route::get('/admin/invoices/{invoice}', [AdminInvoiceController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::get('/admin/invoices/{invoice}/pdf', [AdminInvoiceController::class, 'pdf'])
            ->middleware('throttle:pdf-view');
        Route::post('/admin/invoices', [AdminInvoiceController::class, 'store'])
            ->middleware('throttle:admin-actions');
        Route::put('/admin/invoices/{invoice}', [AdminInvoiceController::class, 'update'])
            ->middleware('throttle:admin-actions');
        Route::delete('/admin/invoices/{invoice}', [AdminInvoiceController::class, 'destroy'])
            ->middleware('throttle:admin-actions');

        // Repairs
        Route::get('/admin/repairs', [AdminRepairController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::patch('/admin/repairs/{repair}/status', [AdminRepairController::class, 'updateStatus'])
            ->middleware('throttle:admin-actions');
        Route::delete('/admin/repairs/{repair}', [AdminRepairController::class, 'destroy'])
            ->middleware('throttle:admin-actions');

        // Payments review
        Route::get('/admin/payments/pending', [AdminPaymentReviewController::class, 'pending'])
            ->middleware('throttle:30,1');
        Route::post('/admin/payments/{payment}/approve', [AdminPaymentReviewController::class, 'approve'])
            ->middleware('throttle:admin-actions');
        Route::post('/admin/payments/{payment}/reject', [AdminPaymentReviewController::class, 'reject'])
            ->middleware('throttle:admin-actions');

        // Parcels
        Route::get('/admin/parcels', [AdminParcelController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/admin/parcels/{parcel}', [AdminParcelController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::post('/admin/parcels', [AdminParcelController::class, 'store'])
            ->middleware('throttle:admin-actions');

        // multipart/form-data
        Route::post('/admin/parcels/{parcel}', [AdminParcelController::class, 'update'])
            ->middleware('throttle:admin-actions');

        Route::post('/admin/parcels/{parcel}/pickup', [AdminParcelController::class, 'pickup'])
            ->middleware('throttle:admin-actions');
        Route::delete('/admin/parcels/{parcel}', [AdminParcelController::class, 'destroy'])
            ->middleware('throttle:admin-actions');

        // Announcements
        Route::get('/admin/announcements', [AdminAnnouncementController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/admin/announcements/{announcement}', [AdminAnnouncementController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::post('/admin/announcements', [AdminAnnouncementController::class, 'store'])
            ->middleware('throttle:admin-actions');

        // multipart/form-data
        Route::post('/admin/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])
            ->middleware('throttle:admin-actions');

        Route::post('/admin/announcements/{announcement}/publish', [AdminAnnouncementController::class, 'publish'])
            ->middleware('throttle:admin-actions');
        Route::post('/admin/announcements/{announcement}/expire', [AdminAnnouncementController::class, 'expire'])
            ->middleware('throttle:admin-actions');
        Route::delete('/admin/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])
            ->middleware('throttle:admin-actions');
    });

    // Tenant APIs
    Route::middleware(['auth:sanctum', 'role:tenant'])->group(function () {
        // Dashboard
        Route::get('/tenant/dashboard/summary', [TenantDashboardController::class, 'summary'])
            ->middleware('throttle:30,1');

        // Profile
        Route::get('/tenant/profile', [TenantProfileController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::put('/tenant/profile', [TenantProfileController::class, 'update'])
            ->middleware('throttle:10,1');

        // Invoices
        Route::get('/tenant/invoices', [TenantInvoiceController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/tenant/invoices/{invoice}', [TenantInvoiceController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::get('/tenant/invoices/{invoice}/pdf', [TenantInvoiceController::class, 'pdf'])
            ->middleware('throttle:pdf-view');

        // Repairs
        Route::get('/tenant/repairs', [TenantRepairController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/tenant/repairs', [TenantRepairController::class, 'store'])
            ->middleware('throttle:repair-store');

        // Payments
        Route::post('/tenant/payments/{invoice}', [TenantPaymentController::class, 'store'])
            ->middleware('throttle:upload-slip');

        // Parcels
        Route::get('/tenant/parcels', [TenantParcelController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/tenant/parcels/{parcel}', [TenantParcelController::class, 'show'])
            ->middleware('throttle:30,1');

        // Announcements
        Route::get('/tenant/announcements', [TenantAnnouncementController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/tenant/announcements/{announcement}', [TenantAnnouncementController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::post('/tenant/announcements/{announcement}/read', [TenantAnnouncementController::class, 'read'])
            ->middleware('throttle:announcement-read');
        Route::get('/tenant/announcements/urgent/active', [TenantAnnouncementController::class, 'urgentActive'])
            ->middleware('throttle:30,1');
    });
});