<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Admin UI controller (Blade only)
 * Actions are via REST API.
 */
class PaymentReviewController extends Controller
{
    public function pending()
    {
        return view('admin.payments.pending');
    }

    public function approve(): void { abort(404); }
    public function reject(): void { abort(404); }
}
