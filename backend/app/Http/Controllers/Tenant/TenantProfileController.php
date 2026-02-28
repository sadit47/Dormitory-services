<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantProfileController extends Controller
{
    private function isAdmin(Request $request): bool
    {
        return $request->user() && $request->user()->role === 'admin';
    }

    // ============== TENANT ONLY ==============
    public function create(Request $request)
    {
        // admin ไม่ควรเข้าหน้านี้
        if ($this->isAdmin($request)) {
            return redirect()->route('admin.dashboard');
        }

        // ถ้ามีแล้วไม่ต้องกรอกซ้ำ
        if ($request->user()->tenant) {
            return redirect()->route('tenant.dashboard');
        }

        return view('tenant.profile.create');
    }

    public function store(Request $request)
    {
        if ($this->isAdmin($request)) {
            return redirect()->route('admin.dashboard');
        }

        $user = $request->user();

        if ($user->tenant) {
            return redirect()->route('tenant.dashboard');
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'address'   => ['nullable', 'string', 'max:500'],
        ]);

        Tenant::create([
            'user_id'    => $user->id,
            'full_name'  => $data['full_name'],
            'phone'      => $data['phone'] ?? '',
            'address'    => $data['address'] ?? '',
        ]);

        return redirect()->route('tenant.dashboard')->with('success', 'บันทึกข้อมูลผู้เช่าเรียบร้อย');
    }

    // ============== BOTH ADMIN / TENANT ==============
    public function edit(Request $request)
    {
        $user = $request->user();

        // ✅ ADMIN: แก้ข้อมูลตัวเอง (ไม่ต้องมี tenant)
        if ($this->isAdmin($request)) {
            return view('admin.profile.edit', compact('user'));
        }

        // ✅ TENANT:
        $tenant = $user->tenant;

        // ถ้ายังไม่มี tenant ให้ไปกรอก create ก่อน
        if (!$tenant) {
            return redirect()->route('tenant.profile.create');
        }

        return view('tenant.profile.edit', compact('user', 'tenant'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        // ✅ ADMIN: update เฉพาะ users
        if ($this->isAdmin($request)) {
            $data = $request->validate([
                'name'  => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
            ]);

            $user->update([
                'name'  => $data['name'],
                'phone' => $data['phone'] ?? null,
            ]);

            return back()->with('success', 'อัปเดตข้อมูลเรียบร้อย');
        }

        // ✅ TENANT: update users + tenants
        $tenant = $user->tenant;
        abort_if(!$tenant, 404);

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'citizen_id'        => ['nullable', 'string', 'max:30'],
            'address'           => ['nullable', 'string', 'max:500'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date'],
        ]);

        $user->update([
            'name'  => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        $tenant->update([
            'citizen_id'        => $data['citizen_id'] ?? null,
            'address'           => $data['address'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'start_date'        => $data['start_date'] ?? null,
            'end_date'          => $data['end_date'] ?? null,
        ]);

        return back()->with('success', 'อัปเดตข้อมูลเรียบร้อย');
    }
}
