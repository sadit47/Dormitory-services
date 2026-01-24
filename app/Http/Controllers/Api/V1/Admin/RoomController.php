<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function meta()
    {
        return response()->json([
            'room_types' => RoomType::select('id','name')->orderBy('name')->get(),
            'statuses' => [
                ['value' => 'vacant', 'label' => 'ว่าง'],
                ['value' => 'occupied', 'label' => 'มีผู้เช่า'],
                ['value' => 'maintenance', 'label' => 'ซ่อมบำรุง'],
            ],
        ]);
    }

    public function index(Request $request)
    {
        $q = Room::query()
            ->with([
                'roomType:id,name',
                'activeAssignment.tenant.user:id,name,email,phone',
            ]);

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $s = $request->string('search');
            $q->where('code', 'like', "%{$s}%");
        }

        $rooms = $q->orderBy('floor')->orderBy('code')->paginate(
            (int) ($request->input('per_page', 10))
        );

        return response()->json($rooms);
    }

    public function show(Room $room)
    {
        $room->load(['roomType:id,name']);
        return response()->json(['data' => $room]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required','string','max:50','unique:rooms,code'],
            'floor' => ['required','integer','min:0'],
            'room_type_id' => ['required','integer','exists:room_types,id'],
            'price_monthly' => ['required','numeric','min:0'],
            'status' => ['required','in:vacant,occupied,maintenance'],
        ]);

        $room = Room::create($data);

        return response()->json([
            'message' => 'Created',
            'data' => $room,
        ], 201);
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'code' => ['sometimes','string','max:50','unique:rooms,code,'.$room->id],
            'floor' => ['sometimes','integer','min:0'],
            'room_type_id' => ['sometimes','integer','exists:room_types,id'],
            'price_monthly' => ['sometimes','numeric','min:0'],
            'status' => ['sometimes','in:vacant,occupied,maintenance'],
        ]);

        $room->update($data);

        return response()->json([
            'message' => 'Updated',
            'data' => $room,
        ]);
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json([
            'message' => 'Deleted',
        ]);
    }

    public function tenant(Room $room)
    {
        $room->load([
            'activeAssignment.tenant.user:id,name,email,phone',
        ]);

        $assignment = $room->activeAssignment;

        return response()->json([
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
