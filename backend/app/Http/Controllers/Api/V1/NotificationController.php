<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $u = $request->user();

        $paginator = Notification::query()
            ->where('user_id', $u->id)
            ->latest('id')
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString();

        return apiPaginate($paginator);
    }

    public function read(Request $request, Notification $notification): JsonResponse
    {
        $u = $request->user();

        if ((int) $notification->user_id !== (int) $u->id) {
            return apiResponse(null, 'Forbidden', 403);
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return apiResponse($notification, 'Updated');
    }

    public function readAll(Request $request): JsonResponse
    {
        $u = $request->user();

        Notification::query()
            ->where('user_id', $u->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return apiResponse(null, 'Updated');
    }
}