<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\{RepairRequest, File, RoomAssignment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairRequestController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        $repairs = RepairRequest::with(['room', 'files'])
            ->where('tenant_id', $tenant->id)
            ->latest('requested_at')
            ->paginate(10);

        return view('tenant.repairs.index', compact('repairs'));
    }

    public function create(Request $request)
    {
        return view('tenant.repairs.create');
    }

    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;

        // หา room ปัจจุบันจาก assignment active
        $activeAssignment = RoomAssignment::where('tenant_id', $tenant->id)
            ->where('status', 'active')->latest()->first();

        if (!$activeAssignment) {
            return back()->withErrors(['room' => 'ไม่พบห้องพักที่กำลังเข้าพัก'])->withInput();
        }

        $data = $request->validate([
            'title' => ['required','string','max:190'],
            'description' => ['nullable','string'],
            'priority' => ['required','in:low,medium,high'],
            'images.*' => ['nullable','file','mimes:jpg,jpeg,png','max:5120'], // 5MB
        ]);

        $repair = RepairRequest::create([
            'tenant_id' => $tenant->id,
            'room_id' => $activeAssignment->room_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'status' => 'submitted',
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $path = Storage::disk('private')->putFile("repairs/{$repair->id}", $img);

                File::create([
                    'owner_user_id' => $request->user()->id,
                    'ref_type' => 'repair',
                    'ref_id' => $repair->id,
                    'disk' => 'private',
                    'path' => $path,
                    'original_name' => $img->getClientOriginalName(),
                    'mime' => $img->getMimeType(),
                    'size' => $img->getSize(),
                    'checksum' => hash_file('sha256', $img->getRealPath()),
                ]);
            }
        }

        return redirect()->route('tenant.repairs.index')->with('success', 'ส่งแจ้งซ่อมสำเร็จ');
    }
}
