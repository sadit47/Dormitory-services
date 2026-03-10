<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Notification;
use App\Models\Parcel;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ParcelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Parcel::with([
            'tenant.user:id,name,email,phone',
            'room:id,code',
            'receivedBy:id,name,email',
            'pickedUpBy:id,name,email',
            'files',
        ]);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('tracking_no', 'like', "%{$q}%")
                  ->orWhere('courier', 'like', "%{$q}%")
                  ->orWhere('sender_name', 'like', "%{$q}%")
                  ->orWhereHas('tenant.user', function ($u) use ($q) {
                      $u->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                  })
                  ->orWhereHas('room', function ($r) use ($q) {
                      $r->where('code', 'like', "%{$q}%");
                  });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $paginator = $query
            ->latest('received_at')
            ->latest('id')
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString();

        return apiPaginate($paginator);
    }

    public function show(Parcel $parcel): JsonResponse
    {
        $parcel->load([
            'tenant.user:id,name,email,phone',
            'room:id,code',
            'receivedBy:id,name,email',
            'pickedUpBy:id,name,email',
            'files',
        ]);

        return apiResponse($parcel);
    }

    public function store(Request $request): JsonResponse
    {
        $u = $request->user();

        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'tracking_no' => ['nullable', 'string', 'max:120'],
            'courier' => ['nullable', 'string', 'max:100'],
            'sender_name' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $parcel = DB::transaction(function () use ($request, $data, $u) {
            $tenant = Tenant::with(['user', 'currentRoom'])->findOrFail($data['tenant_id']);

            $parcel = Parcel::create([
                'tenant_id' => $tenant->id,
                'room_id' => $tenant->currentRoom?->id,
                'tracking_no' => $data['tracking_no'] ?? null,
                'courier' => $data['courier'] ?? null,
                'sender_name' => $data['sender_name'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => 'arrived',
                'received_at' => now(),
                'received_by_user_id' => $u->id,
            ]);

            foreach ($request->file('images', []) as $img) {
                $path = $img->store('parcel_images', 'public');

                File::create([
                    'owner_user_id' => $u->id,
                    'ref_type' => 'parcel_image',
                    'ref_id' => $parcel->id,
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $img->getClientOriginalName(),
                    'mime' => $img->getMimeType(),
                    'size' => $img->getSize(),
                    'checksum' => null,
                ]);
            }

            if ($tenant->user_id) {
                Notification::create([
                    'user_id' => $tenant->user_id,
                    'type' => 'parcel_arrived',
                    'title' => 'มีพัสดุมาถึง',
                    'message' => 'มีพัสดุใหม่มาถึงสำหรับห้อง ' . ($tenant->currentRoom?->code ?? '-'),
                    'ref_type' => 'parcel',
                    'ref_id' => $parcel->id,
                    'is_read' => false,
                ]);
            }

            return $parcel;
        });

        $parcel->load([
            'tenant.user:id,name,email,phone',
            'room:id,code',
            'receivedBy:id,name,email',
            'pickedUpBy:id,name,email',
            'files',
        ]);

        return apiResponse($parcel, 'Created', 201);
    }

    public function update(Request $request, Parcel $parcel): JsonResponse
    {
        $data = $request->validate([
            'tracking_no' => ['nullable', 'string', 'max:120'],
            'courier' => ['nullable', 'string', 'max:100'],
            'sender_name' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string'],
            'status' => ['nullable', 'in:arrived,picked_up,cancelled'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        DB::transaction(function () use ($request, $parcel, $data) {
            $payload = [
                'tracking_no' => $data['tracking_no'] ?? $parcel->tracking_no,
                'courier' => $data['courier'] ?? $parcel->courier,
                'sender_name' => $data['sender_name'] ?? $parcel->sender_name,
                'note' => $data['note'] ?? $parcel->note,
            ];

            if (!empty($data['status'])) {
                $payload['status'] = $data['status'];
            }

            $parcel->update($payload);

            foreach ($request->file('images', []) as $img) {
                $path = $img->store('parcel_images', 'public');

                File::create([
                    'owner_user_id' => $request->user()->id,
                    'ref_type' => 'parcel_image',
                    'ref_id' => $parcel->id,
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $img->getClientOriginalName(),
                    'mime' => $img->getMimeType(),
                    'size' => $img->getSize(),
                    'checksum' => null,
                ]);
            }
        });

        $parcel->refresh()->load([
            'tenant.user:id,name,email,phone',
            'room:id,code',
            'receivedBy:id,name,email',
            'pickedUpBy:id,name,email',
            'files',
        ]);

        return apiResponse($parcel, 'Updated');
    }

    public function pickup(Request $request, Parcel $parcel): JsonResponse
    {
        if ($parcel->status === 'picked_up') {
            return apiResponse($parcel, 'Parcel already picked up', 422);
        }

        $parcel->update([
            'status' => 'picked_up',
            'picked_up_at' => now(),
            'picked_up_by_user_id' => $request->user()->id,
        ]);

        $parcel->load([
            'tenant.user:id,name,email,phone',
            'room:id,code',
            'receivedBy:id,name,email',
            'pickedUpBy:id,name,email',
            'files',
        ]);

        return apiResponse($parcel, 'Updated');
    }

    public function destroy(Parcel $parcel): JsonResponse
    {
        $parcel->load('files');

        DB::transaction(function () use ($parcel) {
            foreach ($parcel->files as $f) {
                if (!empty($f->disk) && !empty($f->path)) {
                    Storage::disk($f->disk)->delete($f->path);
                }
                $f->delete();
            }

            Notification::query()
                ->where('ref_type', 'parcel')
                ->where('ref_id', $parcel->id)
                ->delete();

            $parcel->delete();
        });

        return apiResponse(null, 'Deleted');
    }
}