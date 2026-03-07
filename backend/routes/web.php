<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\RoleLoginController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboard;

use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\PaymentReviewController;
use App\Http\Controllers\Admin\RepairController as AdminRepairController;
use App\Http\Controllers\Admin\AdminProfileController;

use App\Http\Controllers\Tenant\RepairRequestController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\TenantInvoiceController;
use App\Http\Controllers\Tenant\TenantProfileController;

use App\Http\Controllers\FileController;

use L5Swagger\Http\Controllers\SwaggerController;

Route::get('/docs/api-docs.json', [SwaggerController::class, 'docs'])
    ->name('swagger.docs.alias');

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| ✅ Guest Login Selector (ต้องอยู่ก่อน require auth.php)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [RoleLoginController::class, 'choose'])->name('login');
    Route::get('/admin/login', [RoleLoginController::class, 'admin'])->name('login.admin');
    Route::get('/tenant/login', [RoleLoginController::class, 'tenant'])->name('login.tenant');
});

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/home', function () {
        return auth()->user()->role === 'admin'
            ? redirect()->route('admin.dashboard')
            : redirect()->route('tenant.dashboard');
    })->name('home');

    Route::get('/files/{file}', [FileController::class, 'show'])->name('files.show');

    /*
    |----------------------------------------------------------------------
    | ADMIN
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->middleware('role:admin')
        ->name('admin.')
        ->group(function () {

            Route::get('profile/edit', [AdminProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');

            Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

            /*
             |--------------------------------------------------------------
             | ✅ Admin UI (Blade) routes
             | Blade pages will fetch/submit data via REST API only.
             |--------------------------------------------------------------
             */
            Route::resource('rooms', RoomController::class)->only(['index','create','edit']);
            Route::resource('tenants', TenantController::class)->only(['index','create','edit']);
            Route::resource('invoices', AdminInvoiceController::class)->only(['index','create','edit']);

            Route::get('repairs', [AdminRepairController::class, 'index'])->name('repairs.index');
            // Repairs actions now via API

            Route::get('invoices/{invoice}/pdf', [AdminInvoiceController::class, 'pdfInvoice'])->name('invoices.pdf');
            Route::get('invoices/{invoice}/receipt-pdf', [AdminInvoiceController::class, 'pdfReceipt'])->name('receipts.pdf');

            Route::get('payments/pending', [PaymentReviewController::class, 'pending'])->name('payments.pending');
            // Payment approve/reject now via API
        });

    /*
    |----------------------------------------------------------------------
    | TENANT
    |----------------------------------------------------------------------
    */
    Route::prefix('tenant')
  ->middleware(['role:tenant'])
  ->name('tenant.')
  ->group(function () {

    // ✅ profile setup (ไม่ผ่าน tenant.profile middleware)
    Route::get('profile/create', [TenantProfileController::class, 'create'])->name('profile.create');
    Route::post('profile', [TenantProfileController::class, 'store'])->name('profile.store');

    Route::get('profile/edit', [TenantProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [TenantProfileController::class, 'update'])->name('profile.update');

    // ✅ ส่วนอื่นๆ ค่อยคุมด้วย tenant.profile
    Route::middleware('tenant.profile')->group(function () {

      Route::get('/dashboard', [TenantDashboard::class, 'index'])->name('dashboard');

      Route::resource('repairs', RepairRequestController::class)->only(['index', 'create', 'store']);

      Route::get('payments/create/{invoice}', [PaymentController::class, 'create'])->name('payments.create');
      Route::post('payments/{invoice}', [PaymentController::class, 'store'])->name('payments.store');

      Route::get('invoices', [TenantInvoiceController::class, 'index'])->name('invoices.index');
      Route::get('invoices/{invoice}/pdf', [TenantInvoiceController::class, 'pdf'])->name('invoices.pdf');
      Route::get('invoices/{invoice}/receipt-pdf', [TenantInvoiceController::class, 'receiptPdf'])->name('receipts.pdf');

    });
});

});

require __DIR__ . '/auth.php';
