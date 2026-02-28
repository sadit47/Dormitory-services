<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function meta(): JsonResponse
    {
        return apiResponse([
            'room_types' => RoomType::select('id','name')->orderBy('name')->get(),
            'statuses' => [
                ['value' => 'vacant', 'label' => 'ว่าง'],
                ['value' => 'occupied', 'label' => 'มีผู้เช่า'],
                ['value' => 'maintenance', 'label' => 'ซ่อมบำรุง'],
            ],
        ], 'OK');
    }

    public function index(Request $request): JsonResponse
    {
        $q = Room::query()
        ->with([
            'roomType:id,name',

            // ✅ เลือก assignment ที่ยัง active จริง
            'activeAssignment' => function ($qq) {
                $qq->select('id','tenant_id','room_id','start_date','end_date','status')
                ->where('status', 'active')
                ->where(function ($w) {
                    $w->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', now()->toDateString());
                })
                ->orderByDesc('start_date');
            },

            // ✅ tenant + user (เพิ่ม start_date ด้วย ถ้าต้องใช้)
            'activeAssignment.tenant:id,user_id,start_date,end_date',
            'activeAssignment.tenant.user:id,name,email,phone',
        ]);

        if ($request->filled('status')) {
            $q->where('status', (string) $request->string('status'));
        }

        if ($request->filled('search')) {
            $s = (string) $request->string('search');
            $q->where('code', 'like', "%{$s}%");
        }

        $rooms = $q->orderBy('floor')
            ->orderBy('code')
            ->paginate((int) ($request->input('per_page', 10)))
            ->appends($request->query());

        return apiPaginate($rooms);
    }

    public function show(Room $room): JsonResponse
    {
        $room->load(['roomType:id,name']);
        return apiResponse($room);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required','string','max:50','unique:rooms,code'],
            'floor' => ['required','integer','min:0'],
            'room_type_id' => ['required','integer','exists:room_types,id'],
            'price_monthly' => ['required','numeric','min:0'],
            'status' => ['required','in:vacant,occupied,maintenance'],
        ]);

        $room = Room::create($data);

        return apiResponse($room, 'Created', 201);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes','string','max:50','unique:rooms,code,'.$room->id],
            'floor' => ['sometimes','integer','min:0'],
            'room_type_id' => ['sometimes','integer','exists:room_types,id'],
            'price_monthly' => ['sometimes','numeric','min:0'],
            'status' => ['sometimes','in:vacant,occupied,maintenance'],
        ]);

        $room->update($data);

        return apiResponse($room, 'Updated');
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return apiResponse(null, 'Deleted');
    }

    // ยังเก็บไว้ได้ (กรณีอยากดูละเอียดเฉพาะห้อง)
    public function tenant(Room $room): JsonResponse
    {
        $room->load([
            'activeAssignment:id,tenant_id,room_id,start_date,end_date,status',
            'activeAssignment.tenant.user:id,name,email,phone',
        ]);

        $assignment = $room->activeAssignment;

        return apiResponse([
            'room_id' => $room->id,
            'status' => $room->status,
            'tenant' => $assignment?->tenant?->user ? [
                'name' => $assignment->tenant->user->name,
                'email' => $assignment->tenant->user->email,
                'phone' => $assignment->tenant->user->phone,
            ] : null,
            'assignment' => $assignment ? [
                'start_date' => optional($assignment->start_date)->toDateString(),
                'end_date' => optional($assignment->end_date)->toDateString(),
                'status' => $assignment->status,
            ] : null
        ]);
    }
}
