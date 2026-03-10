<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParcelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::where('user_id', $u->id)->firstOrFail();

        $status = trim((string) $request->query('status', ''));

        $query = Parcel::with([
            'room:id,code',
            'receivedBy:id,name,email',
            'pickedUpBy:id,name,email',
            'files',
        ])->where('tenant_id', $tenant->id);

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

    public function show(Request $request, Parcel $parcel): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::where('user_id', $u->id)->firstOrFail();

        if ((int) $parcel->tenant_id !== (int) $tenant->id) {
            return apiResponse(null, 'Forbidden', 403);
        }

        $parcel->load([
            'room:id,code',
            'receivedBy:id,name,email',
            'pickedUpBy:id,name,email',
            'files',
        ]);

        return apiResponse($parcel);
    }
}