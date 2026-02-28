<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\RepairRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RepairRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $tenant = Tenant::where('user_id', $user->id)
            ->with(['currentRoom'])
            ->firstOrFail();

        $repairs = RepairRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(10);

        return view('tenant.repairs.index', compact('tenant', 'repairs'));
    }

    public function create()
    {
        $user = Auth::user();

        $tenant = Tenant::where('user_id', $user->id)
            ->with(['currentRoom'])
            ->firstOrFail();

        return view('tenant.repairs.create', compact('tenant'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $tenant = Tenant::where('user_id', $user->id)
            ->with(['currentRoom'])
            ->firstOrFail();

        if (!$tenant->currentRoom) {
            return back()->withInput()->withErrors([
                'room_id' => 'ไม่พบห้องปัจจุบันของผู้เช่า กรุณาติดต่อแอดมิน'
            ]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            // ถ้าหน้าฟอร์มคุณส่งเป็น images[] (multiple)
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);

        // *** หมายเหตุ: ตาราง repair_requests ของคุณไม่ได้มีคอลัมน์ images จากรูป ***
        // ดังนั้น "อย่า" เก็บ paths ลง repair_requests ตรงๆ (เดี๋ยว mass assign ตัด/หรือคอลัมน์ไม่มี)
        // ถ้าจะเก็บรูป ให้ไปเก็บในตาราง files แยก แล้วผูกด้วย ref_type/ref_id (แบบเดียวกับ payment)

        RepairRequest::create([
            'tenant_id'    => $tenant->id,
            'room_id'      => $tenant->currentRoom->id,
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'priority'     => 'medium',
            'status'       => 'submitted',
            'requested_at' => now(),
            'created_by'   => $user->id,
        ]);

        return redirect()->route('tenant.repairs.index')
            ->with('success', 'ส่งแจ้งซ่อมเรียบร้อย');
    }
}
