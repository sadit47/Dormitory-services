<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Tenant, User, Room, RoomAssignment};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class TenantController extends Controller
{
    public function meta(): JsonResponse
    {
        $vacantRooms = Room::select('id', 'code')
            ->where('status', 'vacant')
            ->orderBy('code')
            ->get();

        return apiResponse([
            'vacant_rooms' => $vacantRooms,
        ], 'OK');
    }

    public function index(Request $req): JsonResponse
    {
        $perPage = max(1, min(100, (int) $req->input('per_page', 10)));
        $q = trim((string) $req->input('q', ''));

        $query = Tenant::query()->with([
            'user:id,name,email,phone',
            'currentRoom:id,code,floor,price_monthly,status',
            'currentAssignment:id,tenant_id,room_id,start_date,end_date,status',
        ]);

        $tenantModel = new Tenant();
        $with = [];

        if (method_exists($tenantModel, 'user')) $with[] = 'user';
        if (method_exists($tenantModel, 'room')) $with[] = 'room';
        if (method_exists($tenantModel, 'currentRoom')) $with[] = 'currentRoom';
        if (method_exists($tenantModel, 'roomAssignment')) $with[] = 'roomAssignment.room';
        if (method_exists($tenantModel, 'roomAssignments')) $with[] = 'roomAssignments.room';

        if (!empty($with)) $query->with($with);

        if ($q !== '') {
            $query->where(function ($w) use ($q, $tenantModel) {

                if (ctype_digit($q)) {
                    $w->orWhere('id', (int)$q);
                }

                try {
                    if (Schema::hasTable('tenants')) {
                        foreach (['id_card', 'citizen_id'] as $col) {
                            if (Schema::hasColumn('tenants', $col)) {
                                $w->orWhere($col, 'like', "%{$q}%");
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // กัน 500
                }

                if (method_exists($tenantModel, 'user')) {
                    $w->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%")
                          ->orWhere('phone', 'like', "%{$q}%");
                    });
                }
            });
        }

        $paginator = $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($req->query());

        return apiPaginate($paginator);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $with = ['user'];
        if (method_exists($tenant, 'currentRoom')) $with[] = 'currentRoom';
        if (method_exists($tenant, 'room')) $with[] = 'room';
        if (method_exists($tenant, 'roomAssignment')) $with[] = 'roomAssignment.room';
        if (method_exists($tenant, 'roomAssignments')) $with[] = 'roomAssignments.room';

        $tenant->load($with);

        return apiResponse($tenant);
    }

    public function store(Request $request): JsonResponse
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
            $tenant = DB::transaction(function () use ($data) {

                $user = User::create([
                    'name'               => $data['name'],
                    'email'              => $data['email'],
                    'password'           => Hash::make($data['password']),
                    'admin_password_enc' => Crypt::encryptString($data['password']),
                    'role'               => 'tenant',
                    'phone'              => $data['phone'] ?? null,
                    'status'             => 'active',
                ]);

                $tenant = Tenant::create([
                    'user_id'            => $user->id,
                    'citizen_id'         => $data['citizen_id'] ?? null,
                    'address'            => $data['address'] ?? null,
                    'emergency_contact'  => $data['emergency_contact'] ?? null,
                    'start_date'         => $data['start_date'] ?? null,
                    'end_date'           => $data['end_date'] ?? null,
                ]);

                if (!empty($data['room_id'])) {
                    $room = Room::lockForUpdate()->findOrFail($data['room_id']);

                    if ($room->status !== 'vacant') {
                        throw new \RuntimeException("ห้อง {$room->code} ไม่ว่างแล้ว");
                    }

                    RoomAssignment::create([
                        'tenant_id'  => $tenant->id,
                        'room_id'    => $room->id,
                        'start_date' => $data['start_date'] ?? now()->toDateString(),
                        'end_date'   => $data['end_date'] ?? null,
                        'status'     => 'active',
                    ]);

                    $room->update(['status' => 'occupied']);
                }

                return $tenant;
            });
        } catch (\Throwable $e) {
            return apiResponse(null, $e->getMessage(), 422);
        }

        $with = ['user'];
        if (method_exists($tenant, 'currentRoom')) $with[] = 'currentRoom';
        if (method_exists($tenant, 'room')) $with[] = 'room';
        if (method_exists($tenant, 'roomAssignment')) $with[] = 'roomAssignment.room';
        if (method_exists($tenant, 'roomAssignments')) $with[] = 'roomAssignments.room';

        $tenant->load($with);

        return apiResponse($tenant, 'Created', 201);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $tenant->load('user');

        $data = $request->validate([
            'name'              => ['sometimes', 'required', 'string', 'max:120'],
            'email'             => ['sometimes', 'required', 'email', 'max:190', "unique:users,email,{$tenant->user->id}"],
            'phone'             => ['nullable', 'string', 'max:50'],
            'password'          => ['nullable', 'string', 'min:8'],
            'citizen_id'        => ['nullable', 'string', 'max:50', "unique:tenants,citizen_id,{$tenant->id}"],
            'address'           => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:190'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
            'room_id'           => ['nullable', 'exists:rooms,id'],
        ]);

        DB::transaction(function () use ($tenant, $data) {
            // 1) update user
            $tenant->user->update([
                'name'  => $data['name'] ?? $tenant->user->name,
                'email' => $data['email'] ?? $tenant->user->email,
                'phone' => $data['phone'] ?? $tenant->user->phone,
            ]);

            if (!empty($data['password'])) {
                $tenant->user->update([
                    'password'           => Hash::make($data['password']),
                    'admin_password_enc' => Crypt::encryptString($data['password']),
                ]);
            }

            // 2) update tenant
            $tenant->update([
                'citizen_id'        => $data['citizen_id'] ?? $tenant->citizen_id,
                'address'           => $data['address'] ?? $tenant->address,
                'emergency_contact' => $data['emergency_contact'] ?? $tenant->emergency_contact,
                'start_date'        => $data['start_date'] ?? $tenant->start_date,
                'end_date'          => $data['end_date'] ?? $tenant->end_date,
            ]);

            // 3) update assignment
            $active = RoomAssignment::where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('end_date')
                      ->orWhereDate('end_date', '>=', now()->toDateString());
                })
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();

            if (array_key_exists('room_id', $data) && !empty($data['room_id'])) {
                $newRoomId = (int) $data['room_id'];

                if (!$active) {
                    $room = Room::lockForUpdate()->findOrFail($newRoomId);
                    if ($room->status !== 'vacant') throw new \RuntimeException("ห้อง {$room->code} ไม่ว่างแล้ว");

                    RoomAssignment::create([
                        'tenant_id'  => $tenant->id,
                        'room_id'    => $room->id,
                        'start_date' => $tenant->start_date ?? now()->toDateString(),
                        'end_date'   => $tenant->end_date ?? null,
                        'status'     => 'active',
                    ]);

                    $room->update(['status' => 'occupied']);
                } else {
                    if ((int) $active->room_id !== $newRoomId) {
                        $oldRoomId = (int) $active->room_id;

                        $active->update([
                            'status'   => 'ended',
                            'end_date' => now()->toDateString(),
                        ]);

                        $oldRoom = Room::lockForUpdate()->find($oldRoomId);
                        if ($oldRoom) $oldRoom->update(['status' => 'vacant']);

                        $newRoom = Room::lockForUpdate()->findOrFail($newRoomId);
                        if ($newRoom->status !== 'vacant') throw new \RuntimeException("ห้อง {$newRoom->code} ไม่ว่างแล้ว");

                        RoomAssignment::create([
                            'tenant_id'  => $tenant->id,
                            'room_id'    => $newRoom->id,
                            'start_date' => $tenant->start_date ?? now()->toDateString(),
                            'end_date'   => $tenant->end_date ?? null,
                            'status'     => 'active',
                        ]);

                        $newRoom->update(['status' => 'occupied']);
                    }
                }
            }

            if ($active) {
                $active->update([
                    'start_date' => $tenant->start_date ?? $active->start_date,
                    'end_date'   => $tenant->end_date ?? $active->end_date,
                ]);
            }
        });

        $with = ['user'];
        if (method_exists($tenant, 'currentRoom')) $with[] = 'currentRoom';
        if (method_exists($tenant, 'currentAssignment')) $with[] = 'currentAssignment';

        $tenant->refresh()->load($with);

        return apiResponse($tenant, 'Updated');
    }

    /**
     * ✅ DELETE /admin/tenants/{tenant}
     * ลบผู้เช่า + คืนห้องว่าง (ถ้าปลอดภัย)
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        // กันลบถ้ามีข้อมูลผูกอยู่ (กัน FK / กันข้อมูลสำคัญหาย)
        try {
            if (method_exists($tenant, 'invoices') && $tenant->invoices()->exists()) {
                return apiResponse(null, 'ลบไม่ได้: มีใบแจ้งหนี้ผูกอยู่กับผู้เช่านี้', 422);
            }
            if (method_exists($tenant, 'repairs') && $tenant->repairs()->exists()) {
                return apiResponse(null, 'ลบไม่ได้: มีรายการแจ้งซ่อมผูกอยู่กับผู้เช่านี้', 422);
            }
            if (method_exists($tenant, 'cleanings') && $tenant->cleanings()->exists()) {
                return apiResponse(null, 'ลบไม่ได้: มีรายการทำความสะอาดผูกอยู่กับผู้เช่านี้', 422);
            }
        } catch (\Throwable $e) {
            return apiResponse(null, 'ตรวจสอบข้อมูลก่อนลบไม่สำเร็จ: ' . $e->getMessage(), 422);
        }

        try {
            DB::transaction(function () use ($tenant) {
                // โหลด user ไว้ลบทีหลัง
                $tenant->load('user');

                // หา assignment active ล่าสุด
                $active = RoomAssignment::where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->orderByDesc('start_date')
                    ->orderByDesc('id')
                    ->first();

                if ($active) {
                    $roomId = (int) $active->room_id;

                    // ปิด assignment
                    $active->update([
                        'status'   => 'ended',
                        'end_date' => now()->toDateString(),
                    ]);

                    // คืนห้องว่าง
                    $room = Room::lockForUpdate()->find($roomId);
                    if ($room) {
                        $room->update(['status' => 'vacant']);
                    }
                }

                // ลบ assignments ทั้งหมดของ tenant นี้ (ถ้าต้องการเก็บ history ก็เอาบรรทัดนี้ออก)
                RoomAssignment::where('tenant_id', $tenant->id)->delete();

                // ลบ tenant
                $tenant->delete();

                // ลบ user (บัญชี login)
                if ($tenant->user) {
                    $tenant->user->delete();
                }
            });
        } catch (\Throwable $e) {
            return apiResponse(null, 'ลบไม่สำเร็จ: ' . $e->getMessage(), 422);
        }

        return apiResponse(null, 'Deleted');
    }
}