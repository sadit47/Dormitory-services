<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Admin UI controller (Blade only)
 * Data operations via REST API.
 */
class RepairController extends Controller
{
    public function index()
    {
        return view('admin.repairs.index');
    }

    public function updateStatus(): void { abort(404); }
    public function destroy(): void { abort(404); }
}
