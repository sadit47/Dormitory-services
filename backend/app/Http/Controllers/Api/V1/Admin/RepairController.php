<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\RepairRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RepairController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $repairs = RepairRequest::with(['tenant.user:id,name,email,phone', 'room:id,code', 'files'])
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where('title', 'like', "%{$q}%")
                    ->orWhereHas('tenant.user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                    })
                    ->orWhereHas('room', function ($r) use ($q) {
                        $r->where('code', 'like', "%{$q}%");
                    });
            })
            ->when($status !== '', fn($qr) => $qr->where('status', $status))
            ->latest('requested_at')
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString();

        return apiPaginate($repairs);
    }

    public function updateStatus(Request $request, RepairRequest $repair): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:submitted,in_progress,done,rejected'],
        ]);

        $repair->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ]);

        return apiResponse($repair, 'Updated');
    }

    public function destroy(RepairRequest $repair): JsonResponse
    {
        $repair->load('files');

        DB::transaction(function () use ($repair) {
            foreach ($repair->files as $f) {
                if (!empty($f->disk) && !empty($f->path)) {
                    Storage::disk($f->disk)->delete($f->path);
                }
                $f->delete();
            }
            $repair->delete();
        });

        return apiResponse(null, 'Deleted');
    }
}
