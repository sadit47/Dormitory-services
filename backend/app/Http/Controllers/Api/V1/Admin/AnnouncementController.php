<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use App\Models\File;
use App\Models\Notification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $type = trim((string) $request->query('type', ''));

        $query = Announcement::with([
            'creator:id,name,email',
            'targets',
            'files',
        ]);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('content', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        $paginator = $query
            ->orderByDesc('is_pinned')
            ->latest('id')
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString();

        return apiPaginate($paginator);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        $announcement->load([
            'creator:id,name,email',
            'targets',
            'reads.user:id,name,email',
            'files',
        ]);

        return apiResponse($announcement);
    }

    public function store(Request $request): JsonResponse
    {
        $u = $request->user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:general,urgent,maintenance'],
            'status' => ['nullable', 'in:draft,published,expired'],
            'is_pinned' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'targets' => ['nullable', 'array'],
            'targets.*.target_type' => ['required_with:targets', 'in:all,room,tenant'],
            'targets.*.target_id' => ['nullable', 'integer'],

            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $announcement = DB::transaction(function () use ($request, $data, $u) {
            $announcement = Announcement::create([
                'title' => $data['title'],
                'content' => $data['content'],
                'type' => $data['type'],
                'status' => $data['status'] ?? 'draft',
                'is_pinned' => (bool) ($data['is_pinned'] ?? false),
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'created_by_user_id' => $u->id,
            ]);

            $targets = $data['targets'] ?? [['target_type' => 'all', 'target_id' => null]];

            foreach ($targets as $target) {
                AnnouncementTarget::create([
                    'announcement_id' => $announcement->id,
                    'target_type' => $target['target_type'],
                    'target_id' => $target['target_id'] ?? null,
                ]);
            }

            foreach ($request->file('images', []) as $img) {
                $path = $img->store('announcement_images', 'public');

                File::create([
                    'owner_user_id' => $u->id,
                    'ref_type' => 'announcement_image',
                    'ref_id' => $announcement->id,
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $img->getClientOriginalName(),
                    'mime' => $img->getMimeType(),
                    'size' => $img->getSize(),
                    'checksum' => null,
                ]);
            }

            if (($data['status'] ?? 'draft') === 'published') {
                $this->pushNotifications($announcement);
            }

            return $announcement;
        });

        $announcement->load([
            'creator:id,name,email',
            'targets',
            'files',
        ]);

        return apiResponse($announcement, 'Created', 201);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:190'],
            'content' => ['sometimes', 'required', 'string'],
            'type' => ['nullable', 'in:general,urgent,maintenance'],
            'status' => ['nullable', 'in:draft,published,expired'],
            'is_pinned' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'targets' => ['nullable', 'array'],
            'targets.*.target_type' => ['required_with:targets', 'in:all,room,tenant'],
            'targets.*.target_id' => ['nullable', 'integer'],

            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        DB::transaction(function () use ($request, $announcement, $data) {
            $announcement->update([
                'title' => $data['title'] ?? $announcement->title,
                'content' => $data['content'] ?? $announcement->content,
                'type' => $data['type'] ?? $announcement->type,
                'status' => $data['status'] ?? $announcement->status,
                'is_pinned' => array_key_exists('is_pinned', $data) ? (bool)$data['is_pinned'] : $announcement->is_pinned,
                'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $announcement->starts_at,
                'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $announcement->ends_at,
            ]);

            if (array_key_exists('targets', $data)) {
                $announcement->targets()->delete();

                $targets = $data['targets'] ?: [['target_type' => 'all', 'target_id' => null]];

                foreach ($targets as $target) {
                    AnnouncementTarget::create([
                        'announcement_id' => $announcement->id,
                        'target_type' => $target['target_type'],
                        'target_id' => $target['target_id'] ?? null,
                    ]);
                }
            }

            foreach ($request->file('images', []) as $img) {
                $path = $img->store('announcement_images', 'public');

                File::create([
                    'owner_user_id' => $request->user()->id,
                    'ref_type' => 'announcement_image',
                    'ref_id' => $announcement->id,
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $img->getClientOriginalName(),
                    'mime' => $img->getMimeType(),
                    'size' => $img->getSize(),
                    'checksum' => null,
                ]);
            }
        });

        $announcement->refresh()->load([
            'creator:id,name,email',
            'targets',
            'files',
        ]);

        return apiResponse($announcement, 'Updated');
    }

    public function publish(Announcement $announcement): JsonResponse
    {
        $announcement->update([
            'status' => 'published',
        ]);

        $this->pushNotifications($announcement->fresh(['targets']));

        return apiResponse($announcement->fresh(['creator:id,name,email', 'targets', 'files']), 'Published');
    }

    public function expire(Announcement $announcement): JsonResponse
    {
        $announcement->update([
            'status' => 'expired',
            'ends_at' => now(),
        ]);

        return apiResponse($announcement->fresh(['creator:id,name,email', 'targets', 'files']), 'Expired');
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->load('files');

        DB::transaction(function () use ($announcement) {
            foreach ($announcement->files as $f) {
                if (!empty($f->disk) && !empty($f->path)) {
                    Storage::disk($f->disk)->delete($f->path);
                }
                $f->delete();
            }

            Notification::query()
                ->where('ref_type', 'announcement')
                ->where('ref_id', $announcement->id)
                ->delete();

            $announcement->delete();
        });

        return apiResponse(null, 'Deleted');
    }

    private function pushNotifications(Announcement $announcement): void
    {
        $announcement->loadMissing('targets');

        $userIds = $this->resolveTargetUserIds($announcement);

        $type = $announcement->type === 'urgent'
            ? 'urgent_announcement'
            : 'announcement';

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $announcement->title,
                'message' => mb_substr(strip_tags($announcement->content), 0, 250),
                'ref_type' => 'announcement',
                'ref_id' => $announcement->id,
                'is_read' => false,
            ]);
        }
    }

    private function resolveTargetUserIds(Announcement $announcement): array
    {
        $targets = $announcement->targets;

        if ($targets->isEmpty()) {
            return User::query()
                ->where('role', 'tenant')
                ->pluck('id')
                ->unique()
                ->values()
                ->all();
        }

        $hasAll = $targets->contains(fn ($t) => $t->target_type === 'all');

        if ($hasAll) {
            return User::query()
                ->where('role', 'tenant')
                ->pluck('id')
                ->unique()
                ->values()
                ->all();
        }

        $tenantIds = $targets->where('target_type', 'tenant')->pluck('target_id')->filter()->values();
        $roomIds = $targets->where('target_type', 'room')->pluck('target_id')->filter()->values();

        $userIds = collect();

        if ($tenantIds->isNotEmpty()) {
            $userIds = $userIds->merge(
                Tenant::query()
                    ->whereIn('id', $tenantIds)
                    ->pluck('user_id')
            );
        }

        if ($roomIds->isNotEmpty()) {
            $userIds = $userIds->merge(
                Tenant::query()
                    ->whereHas('currentAssignment', function ($q) use ($roomIds) {
                        $q->whereIn('room_id', $roomIds)
                          ->where('status', 'active');
                    })
                    ->pluck('user_id')
            );
        }

        return $userIds
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}