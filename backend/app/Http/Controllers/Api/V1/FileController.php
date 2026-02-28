<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\File;
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
        if (!$isAdmin && (int) $file->owner_user_id !== (int) $u->id) {
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
