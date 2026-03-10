<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\File;
use App\Models\Parcel;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    public function show(Request $request, File $file): BinaryFileResponse|JsonResponse
    {
        $u = $request->user();
        if (!$u) {
            return apiResponse(null, 'Unauthenticated', 401);
        }

        $isAdmin = ($u->role ?? null) === 'admin';
        $allowed = $isAdmin || ((int) $file->owner_user_id === (int) $u->id);

        if (!$allowed && ($u->role ?? null) === 'tenant') {
            $tenant = Tenant::where('user_id', $u->id)->first();

            if ($tenant) {
                if ($file->ref_type === 'parcel_image') {
                    $parcel = Parcel::find($file->ref_id);
                    if ($parcel && (int) $parcel->tenant_id === (int) $tenant->id) {
                        $allowed = true;
                    }
                }

                if ($file->ref_type === 'announcement_image') {
                    $announcement = Announcement::with('targets')->find($file->ref_id);

                    if ($announcement && $announcement->status === 'published') {
                        if ($announcement->targets->isEmpty()) {
                            $allowed = true;
                        } else {
                            foreach ($announcement->targets as $target) {
                                if ($target->target_type === 'all') {
                                    $allowed = true;
                                    break;
                                }
                                if ($target->target_type === 'tenant' && (int) $target->target_id === (int) $tenant->id) {
                                    $allowed = true;
                                    break;
                                }
                                if (
                                    $target->target_type === 'room' &&
                                    (int) $target->target_id === (int) ($tenant->currentAssignment?->room_id)
                                ) {
                                    $allowed = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$allowed) {
            return apiResponse(null, 'Forbidden', 403);
        }

        $disk = $file->disk ?? 'public';
        $fullPath = Storage::disk($disk)->path($file->path);

        if (!is_file($fullPath)) {
            return apiResponse(null, 'File not found', 404);
        }

        return response()->file($fullPath);
    }
}