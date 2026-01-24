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

        return response()->json([
            'vacant_rooms' => $vacantRooms,
        ]);
    }

    public function index(Request $req): JsonResponse
    {
        $perPage = (int) $req->input('per_page', 10);
        $q = trim((string) $req->input('q', ''));

        $query = Tenant::query();

        // โหลด relation เฉพาะที่มีจริง (กัน 500)
        $tenantModel = new Tenant();
        $with = [];

        if (method_exists($tenantModel, 'user')) $with[] = 'user';
        if (method_exists($tenantModel, 'room')) $with[] = 'room';
        if (method_exists($tenantModel, 'currentRoom')) $with[] = 'currentRoom';
        if (method_exists($tenantModel, 'roomAssignment')) $with[] = 'roomAssignment.room';
        if (method_exists($tenantModel, 'roomAssignments')) $with[] = 'roomAssignments.room';

        if (!empty($with)) {
            $query->with($with);
        }

        // Search แบบปลอดภัย (กันพังกรณี table/column ไม่ตรง)
        if ($q !== '') {
            $query->where(function ($w) use ($q, $tenantModel) {
                // id
                $w->orWhere('id', $q);

                // tenant columns (เช็ค table/column ก่อน + กัน exception)
                try {
                    if (Schema::hasTable('tenants')) {
                        foreach (['id_card', 'citizen_id', 'phone'] as $col) {
                            if (Schema::hasColumn('tenants', $col)) {
                                $w->orWhere($col, 'like', "%{$q}%");
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // ไม่ให้ 500
                }

                // search ผ่าน user ถ้ามี relation
                if (method_exists($tenantModel, 'user')) {
                    $w->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                    });
                }
            });
        }

        return response()->json($query->paginate($perPage));
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $with = ['user'];
        if (method_exists($tenant, 'currentRoom')) $with[] = 'currentRoom';
        if (method_exists($tenant, 'room')) $with[] = 'room';
        if (method_exists($tenant, 'roomAssignment')) $with[] = 'roomAssignment.room';
        if (method_exists($tenant, 'roomAssignments')) $with[] = 'roomAssignments.room';

        $tenant->load($with);

        return response()->json([
            'data' => $tenant,
        ]);
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
                        'end_date'   => null,
                        'status'     => 'active',
                    ]);

                    $room->update(['status' => 'occupied']);
                }

                return $tenant;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        // load relation เฉพาะที่มีจริง
        $with = ['user'];
        if (method_exists($tenant, 'currentRoom')) $with[] = 'currentRoom';
        if (method_exists($tenant, 'room')) $with[] = 'room';
        if (method_exists($tenant, 'roomAssignment')) $with[] = 'roomAssignment.room';
        if (method_exists($tenant, 'roomAssignments')) $with[] = 'roomAssignments.room';

        $tenant->load($with);

        return response()->json([
            'message' => 'Created',
            'data' => $tenant,
        ], 201);
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
        ]);

        DB::transaction(function () use ($tenant, $data) {
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

            $tenant->update([
                'citizen_id'        => $data['citizen_id'] ?? $tenant->citizen_id,
                'address'           => $data['address'] ?? $tenant->address,
                'emergency_contact' => $data['emergency_contact'] ?? $tenant->emergency_contact,
                'start_date'        => $data['start_date'] ?? $tenant->start_date,
                'end_date'          => $data['end_date'] ?? $tenant->end_date,
            ]);
        });

        // load relation เฉพาะที่มีจริง
        $with = ['user'];
        if (method_exists($tenant, 'currentRoom')) $with[] = 'currentRoom';
        if (method_exists($tenant, 'room')) $with[] = 'room';
        if (method_exists($tenant, 'roomAssignment')) $with[] = 'roomAssignment.room';
        if (method_exists($tenant, 'roomAssignments')) $with[] = 'roomAssignments.room';

        $tenant->refresh()->load($with);

        return response()->json([
            'message' => 'Updated',
            'data' => $tenant,
        ]);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->delete();

        return response()->json([
            'message' => 'Deleted',
        ]);
    }
}
