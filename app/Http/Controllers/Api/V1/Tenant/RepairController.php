<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\{RepairRequest, File, RoomAssignment};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) {
            return response()->json(['message' => 'Tenant profile not found'], 409);
        }

        $repairs = RepairRequest::with(['room:id,code,floor', 'files'])
            ->where('tenant_id', $tenant->id)
            ->latest('requested_at')
            ->paginate((int) $request->input('per_page', 10));

        return response()->json($repairs);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) {
            return response()->json(['message' => 'Tenant profile not found'], 409);
        }

        $activeAssignment = RoomAssignment::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->latest()->first();

        if (!$activeAssignment) {
            return response()->json(['message' => 'ไม่พบห้องพักที่กำลังเข้าพัก'], 422);
        }

        $data = $request->validate([
            'title' => ['required','string','max:190'],
            'description' => ['nullable','string'],
            'priority' => ['required','in:low,medium,high'],
            'images.*' => ['nullable','file','mimes:jpg,jpeg,png','max:5120'],
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

        $repair->load(['room', 'files']);

        return response()->json([
            'message' => 'Created',
            'data' => $repair,
        ], 201);
    }
}
