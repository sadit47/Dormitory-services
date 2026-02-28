<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request, int $invoiceId): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::where('user_id', $u->id)->firstOrFail();

        $data = $request->validate([
            'amount'  => ['required', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'slip'    => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $invoice = Invoice::where('id', $invoiceId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        return DB::transaction(function () use ($request, $u, $invoice, $data): JsonResponse {

            $payment = Payment::create([
                'invoice_id'    => $invoice->id,
                'payer_user_id' => $u->id,
                'amount'        => $data['amount'],
                'method'        => 'transfer',
                'paid_at'       => $data['paid_at'],
                'status'        => 'waiting',
            ]);

            $slip = $request->file('slip');

            if ($slip) {
                $path = $slip->store('payment_slips', 'public');

                File::create([
                    'owner_user_id' => $u->id,
                    'ref_type'      => 'payment',
                    'ref_id'        => $payment->id,
                    'disk'          => 'public',
                    'path'          => $path,
                    'original_name' => $slip->getClientOriginalName(),
                    'mime'          => $slip->getMimeType(),
                    'size'          => $slip->getSize(),
                    'checksum'      => null,
                ]);
            }

            return apiResponse([
                'id'         => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'amount'     => $payment->amount,
                'status'     => $payment->status,
            ], 'created', 201);
        });
    }
}
