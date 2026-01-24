<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant?->id;
        if (!$tenantId) {
            return response()->json(['message' => 'Tenant profile not found'], 409);
        }

        $invoices = Invoice::with(['room:id,code,floor'])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->paginate((int) $request->input('per_page', 10));

        return response()->json($invoices);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $tenantId = $request->user()?->tenant?->id;
        if (!$tenantId || $invoice->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $invoice->load(['tenant.user', 'room', 'items', 'payments']);

        return response()->json(['data' => $invoice]);
    }
}
