<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\RepairRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::where('user_id', $u->id)->firstOrFail();

        $perPage = (int) $request->input('per_page', 10);

        $repairs = RepairRequest::query()
            ->where('tenant_id', $tenant->id)
            // ✅ อย่า select room_no/name เพราะตาราง rooms ของคุณใช้ code
            ->with([
                'room:id,code',
                'files:id,ref_id,ref_type,disk,path,original_name,mime,size,created_at',
            ])
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return apiPaginate($repairs);
    }

   public function store(Request $request): JsonResponse
    {
        $u = $request->user();

        $tenant = Tenant::where('user_id', $u->id)
            ->with(['currentRoom' => function ($q) {
                $q->select('rooms.id', 'rooms.code'); // ✅ แก้ ambiguous
            }])
            ->firstOrFail();

        if (!$tenant->currentRoom) {
            return apiResponse(null, 'ไม่พบห้องปัจจุบันของผู้เช่า กรุณาติดต่อแอดมิน', 422);
        }

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority'    => ['nullable', 'in:low,medium,high'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $repair = DB::transaction(function () use ($request, $tenant, $u, $data) {

            $repair = RepairRequest::create([
                'tenant_id'    => $tenant->id,
                'room_id'      => $tenant->currentRoom->id,
                'title'        => $data['title'],
                'description'  => $data['description'] ?? null,
                'priority'     => $data['priority'] ?? 'medium',
                'status'       => 'submitted',
                'requested_at' => now(),
                'created_by'   => $u->id,
            ]);

            $files = $request->file('images');
            if ($files) {
                foreach ($files as $img) {
                    $path = $img->store('repairs', 'public');

                    File::create([
                        'owner_user_id' => $u->id,
                        'ref_type'      => 'repair_image',
                        'ref_id'        => $repair->id,
                        'disk'          => 'public',
                        'path'          => $path,
                        'original_name' => $img->getClientOriginalName(),
                        'mime'          => $img->getClientMimeType(),
                        'size'          => $img->getSize(),
                    ]);
                }
            }

            return $repair;
        });

        $repair->load([
            'room:id,code',
            'files:id,ref_id,ref_type,disk,path,original_name,mime,size,created_at',
        ]);

        return apiResponse($repair, 'created', 201);
    }
}