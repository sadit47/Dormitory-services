<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $u = $request->user();

        $tenant = Tenant::where('user_id', $u->id)->firstOrFail();

        // แก้ปัญหา SQLSTATE 1052 id ambiguous:
        // ห้าม select แบบ currentRoom:id,... เพราะ relationship join แล้ว id ซ้ำ
        $tenant->load(['currentRoom']);

        return apiResponse([
            'user' => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role'  => $u->role,
            ],
            'tenant' => $tenant->only([
                'id', 'citizen_id', 'address', 'emergency_contact', 'start_date', 'end_date'
            ]),
            'current_room' => $tenant->currentRoom,
        ], 'OK');
    }

    public function update(Request $request): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::where('user_id', $u->id)->firstOrFail();

        $data = $request->validate([
            'citizen_id'        => ['nullable', 'string', 'max:50'],
            'address'           => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date'],
        ]);

        $tenant->fill($data);
        $tenant->save();

        $tenant->load(['currentRoom']);

        return apiResponse([
            'tenant'       => $tenant->only(['id','citizen_id','address','emergency_contact','start_date','end_date']),
            'current_room' => $tenant->currentRoom,
        ], 'updated');
    }
}
