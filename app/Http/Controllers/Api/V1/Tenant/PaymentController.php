<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Payment, File};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (!$tenant || $invoice->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'amount' => ['required','numeric','min:0.01'],
            'paid_at' => ['required','date'],
            'slip' => ['required','file','mimes:jpg,jpeg,png,pdf','max:5120'],
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payer_user_id' => $request->user()->id,
            'amount' => $data['amount'],
            'method' => 'transfer',
            'paid_at' => $data['paid_at'],
            'status' => 'waiting',
        ]);

        $slip = $request->file('slip');
        $path = Storage::disk('private')->putFile("payments/{$payment->id}", $slip);

        File::create([
            'owner_user_id' => $request->user()->id,
            'ref_type' => 'payment',
            'ref_id' => $payment->id,
            'disk' => 'private',
            'path' => $path,
            'original_name' => $slip->getClientOriginalName(),
            'mime' => $slip->getMimeType(),
            'size' => $slip->getSize(),
            'checksum' => hash_file('sha256', $slip->getRealPath()),
        ]);

        $payment->load(['invoice', 'files']);

        return response()->json([
            'message' => 'Created',
            'data' => $payment,
        ], 201);
    }
}
