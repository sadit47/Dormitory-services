<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Tenant, User};
use App\Models\Room;
use App\Models\RoomAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');

        $tenants = Tenant::with(['user', 'currentRoom'])
            ->when($q, function ($qr) use ($q) {
                $qr->whereHas('user', fn($u) =>
                    $u->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                )->orWhere('citizen_id', 'like', "%{$q}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.tenants.index', compact('tenants', 'q'));
    }

    public function create()
    {
        $vacantRooms = Room::where('status', 'vacant')
            ->orderBy('floor')->orderBy('code')
            ->get();

        return view('admin.tenants.create', compact('vacantRooms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:120'],
            'email'             => ['required', 'email', 'max:190', 'unique:users,email'],
            'password'          => ['required', 'string', 'min:8'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'citizen_id'        => ['nullable', 'string', 'max:50', 'unique:tenants,citizen_id'],
            'address'           => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:190'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
            'room_id'           => ['nullable', 'exists:rooms,id'],
        ]);

        try {
            DB::transaction(function () use ($data) {

                // 1) create user
                $user = User::create([
                    'name'               => $data['name'],
                    'email'              => $data['email'],
                    'password'           => Hash::make($data['password']),
                    'admin_password_enc' => Crypt::encryptString($data['password']),
                    'role'               => 'tenant',
                    'phone'              => $data['phone'] ?? null,
                    'status'             => 'active',
                ]);

                // 2) create tenant
                $tenant = Tenant::create([
                    'user_id'            => $user->id,
                    'citizen_id'         => $data['citizen_id'] ?? null,
                    'address'            => $data['address'] ?? null,
                    'emergency_contact'  => $data['emergency_contact'] ?? null,
                    'start_date'         => $data['start_date'] ?? null,
                    'end_date'           => $data['end_date'] ?? null,
                ]);

                // 3) ถ้าเลือกห้องมา → ล็อกห้อง, เช็คว่าง, สร้าง assignment, แล้วเปลี่ยนสถานะห้องเป็น occupied
                if (!empty($data['room_id'])) {
                    $room = Room::lockForUpdate()->findOrFail($data['room_id']);

                    if ($room->status !== 'vacant') {
                        throw new \Exception("ห้อง {$room->code} ไม่ว่างแล้ว");
                    }

                    RoomAssignment::create([
                        'tenant_id'  => $tenant->id,
                        'room_id'    => $room->id,
                        'start_date' => $data['start_date'] ?? now()->toDateString(),
                        'end_date'   => null,
                        'status'     => 'active',
                    ]);

                    $room->update(['status' => 'occupied']);
                }
            });
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.tenants.index')->with('success', 'เพิ่มผู้เช่าสำเร็จ');
    }

    public function edit(Tenant $tenant)
    {
        $tenant->load('user');
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $tenant->load('user');

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:120'],
            'email'             => ['required', 'email', 'max:190', "unique:users,email,{$tenant->user->id}"],
            'phone'             => ['nullable', 'string', 'max:50'],
            'password'          => ['nullable', 'string', 'min:8'],
            'citizen_id'        => ['nullable', 'string', 'max:50', "unique:tenants,citizen_id,{$tenant->id}"],
            'address'           => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:190'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        DB::transaction(function () use ($tenant, $data) {
            $tenant->user->update([
                'name'  => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);

            if (!empty($data['password'])) {
                $tenant->user->update([
                    'password'           => Hash::make($data['password']),
                    'admin_password_enc' => Crypt::encryptString($data['password']),
                ]);
            }

            $tenant->update([
                'citizen_id'        => $data['citizen_id'] ?? null,
                'address'           => $data['address'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'start_date'        => $data['start_date'] ?? null,
                'end_date'          => $data['end_date'] ?? null,
            ]);
        });

        return redirect()->route('admin.tenants.index')->with('success', 'แก้ไขผู้เช่าสำเร็จ');
    }

    public function currentRoom(Tenant $tenant): JsonResponse
    {
    $tenant->load('currentRoom');

        return response()->json([
            'room_id'   => $tenant->currentRoom?->id,
            'room_code' => $tenant->currentRoom?->code,
        ]);
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('admin.tenants.index')->with('success', 'ลบผู้เช่าสำเร็จ');
    }
}
