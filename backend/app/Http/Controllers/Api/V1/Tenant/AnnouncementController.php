<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::with(['currentAssignment'])->where('user_id', $u->id)->firstOrFail();

        $type = trim((string) $request->query('type', ''));

        $query = Announcement::with([
            'creator:id,name,email',
            'targets',
            'files',
        ])
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', now());
            })
            ->when($type !== '', fn ($qr) => $qr->where('type', $type))
            ->where(function ($q) use ($tenant) {
                $q->whereDoesntHave('targets')
                  ->orWhereHas('targets', function ($t) use ($tenant) {
                      $t->where('target_type', 'all')
                        ->orWhere(function ($x) use ($tenant) {
                            $x->where('target_type', 'tenant')
                              ->where('target_id', $tenant->id);
                        })
                        ->orWhere(function ($x) use ($tenant) {
                            $x->where('target_type', 'room')
                              ->where('target_id', $tenant->currentAssignment?->room_id);
                        });
                  });
            });

        $paginator = $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString();

        return apiPaginate($paginator);
    }

    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::with(['currentAssignment'])->where('user_id', $u->id)->firstOrFail();

        if (!$this->canView($announcement, $tenant)) {
            return apiResponse(null, 'Forbidden', 403);
        }

        $announcement->load([
            'creator:id,name,email',
            'targets',
            'files',
        ]);

        return apiResponse($announcement);
    }

    public function read(Request $request, Announcement $announcement): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::with(['currentAssignment'])->where('user_id', $u->id)->firstOrFail();

        if (!$this->canView($announcement, $tenant)) {
            return apiResponse(null, 'Forbidden', 403);
        }

        $row = AnnouncementRead::firstOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $u->id,
            ],
            [
                'read_at' => now(),
            ]
        );

        if (!$row->read_at) {
            $row->update(['read_at' => now()]);
        }

        return apiResponse([
            'announcement_id' => $announcement->id,
            'read_at' => $row->read_at,
        ], 'Updated');
    }

    public function urgentActive(Request $request): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::with(['currentAssignment'])->where('user_id', $u->id)->firstOrFail();

        $items = Announcement::with(['creator:id,name,email', 'targets', 'files'])
            ->where('type', 'urgent')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) use ($tenant) {
                $q->whereDoesntHave('targets')
                  ->orWhereHas('targets', function ($t) use ($tenant) {
                      $t->where('target_type', 'all')
                        ->orWhere(function ($x) use ($tenant) {
                            $x->where('target_type', 'tenant')
                              ->where('target_id', $tenant->id);
                        })
                        ->orWhere(function ($x) use ($tenant) {
                            $x->where('target_type', 'room')
                              ->where('target_id', $tenant->currentAssignment?->room_id);
                        });
                  });
            })
            ->orderByDesc('is_pinned')
            ->latest('starts_at')
            ->latest('id')
            ->get();

        return apiResponse($items);
    }

    private function canView(Announcement $announcement, Tenant $tenant): bool
    {
        if ($announcement->status !== 'published') {
            return false;
        }

        if ($announcement->starts_at && $announcement->starts_at->gt(now())) {
            return false;
        }

        if ($announcement->ends_at && $announcement->ends_at->lt(now())) {
            return false;
        }

        $announcement->loadMissing('targets');

        if ($announcement->targets->isEmpty()) {
            return true;
        }

        foreach ($announcement->targets as $target) {
            if ($target->target_type === 'all') {
                return true;
            }

            if ($target->target_type === 'tenant' && (int) $target->target_id === (int) $tenant->id) {
                return true;
            }

            if ($target->target_type === 'room' && (int) $target->target_id === (int) ($tenant->currentAssignment?->room_id)) {
                return true;
            }
        }

        return false;
    }
}