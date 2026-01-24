<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;

/**
 * Admin UI controller (Blade only)
 * Data operations must be done via REST API (routes/api.php).
 */
class TenantController extends Controller
{
    public function index()
    {
        return view('admin.tenants.index');
    }

    public function create()
    {
        return view('admin.tenants.create');
    }

    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.edit', ['tenantId' => $tenant->id]);
    }

    // Legacy web endpoints disabled
    public function store(): void { abort(404); }
    public function update(): void { abort(404); }
    public function destroy(): void { abort(404); }
    public function show(): void { abort(404); }
    public function currentRoom(): void { abort(404); }
}
