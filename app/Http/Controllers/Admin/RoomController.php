<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Models\RoomType;
use Illuminate\Validation\Rule;


class RoomController extends Controller
{
    public function index(Request $request)
{
    $q      = trim((string) $request->query('q', ''));
    $status = trim((string) $request->query('status', ''));
    $typeId = $request->query('type_id');

    $rooms = Room::query()
        ->with(['roomType', 'activeAssignment.tenant.user'])
        ->when($q !== '', function ($query) use ($q) {
            $query->where('code', 'like', "%{$q}%")
                  ->orWhere('floor', 'like', "%{$q}%");
        })
        ->when(in_array($status, ['vacant','occupied','maintenance'], true), fn($query) => $query->where('status', $status))
        ->when(!empty($typeId), fn($query) => $query->where('room_type_id', $typeId))
        ->orderBy('floor')->orderBy('code')
        ->paginate(12)
        ->withQueryString();

        $roomTypes = RoomType::orderBy('name')->get();

    return view('admin.rooms.index', compact('rooms','roomTypes','q','status','typeId'));
}


    public function create()
    {
        $roomTypes = RoomType::orderBy('name')->get();
        return view('admin.rooms.create', compact('roomTypes'));
    }

    public function store(Request $request)
    {
    $data = $request->validate([
        'code'          => ['required','string','max:50', Rule::unique('rooms','code')],
        'floor'         => ['required','integer','min:1'],
        'room_type_id'  => ['nullable','exists:room_types,id'],
        'price_monthly' => ['required','numeric','min:0'],
        'status'        => ['required', Rule::in(['vacant','occupied','maintenance'])],
    ]);

    Room::create($data);

    return redirect()->route('admin.rooms.index')->with('success', 'เพิ่มห้องพักเรียบร้อย');
    }

    public function edit(Room $room)
    {
        $roomTypes = RoomType::orderBy('name')->get();
        return view('admin.rooms.edit', compact('room','roomTypes'));
    }

    public function update(Request $request, Room $room)
    {
    $data = $request->validate([
        'code'          => ['required','string','max:50', Rule::unique('rooms','code')->ignore($room->id)],
        'floor'         => ['required','integer','min:1'],
        'room_type_id'  => ['nullable','exists:room_types,id'],
        'price_monthly' => ['required','numeric','min:0'],
        'status'        => ['required', Rule::in(['vacant','occupied','maintenance'])],
    ]);

    $room->update($data);

    return redirect()->route('admin.rooms.index')->with('success', 'แก้ไขห้องพักเรียบร้อย');
    }

    public function destroy(Room $room)
    {
        // กันลบถ้ามีผู้เช่าปัจจุบัน
        $room->load('activeAssignment');

        if ($room->activeAssignment) {
            return back()->with('error', 'ลบไม่ได้: ห้องนี้มีผู้เช่าอยู่ (active)');
        }

        $room->delete();

        return back()->with('success', 'ลบห้องพักเรียบร้อย');
    }

    public function tenant(Room $room)
    {
        $room->load(['activeAssignment.tenant']);

        if (!$room->activeAssignment?->tenant) {
            return back()->with('error', 'ห้องนี้ยังไม่มีผู้เช่าปัจจุบัน');
        }

        // ถ้ามี route show ผู้เช่า
        return redirect()->route('admin.tenants.edit', $room->activeAssignment->tenant->id);
    }
}
