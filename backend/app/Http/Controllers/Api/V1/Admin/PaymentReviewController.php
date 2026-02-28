<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentReviewController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $perPage = (int)($request->query('per_page', 20));

        $payments = Payment::query()
            ->where('status', 'waiting')
            ->with([
                'invoice.tenant.user',
                'files'
            ])
            ->latest()
            ->paginate($perPage);

        $payments->getCollection()->transform(function ($p) {
            $slip = $p->files->sortByDesc('id')->first();

            return [
                'id' => $p->id,
                'amount' => $p->amount,
                'status' => $p->status,
                'paid_at' => $p->paid_at,
                'created_at' => $p->created_at,

                'invoice' => $p->invoice ? [
                    'id' => $p->invoice->id,
                    'invoice_no' => $p->invoice->invoice_no,
                    'tenant' => $p->invoice->tenant ? [
                        'id' => $p->invoice->tenant->id,
                        'user' => $p->invoice->tenant->user ? [
                            'id' => $p->invoice->tenant->user->id,
                            'name' => $p->invoice->tenant->user->name,
                            'email' => $p->invoice->tenant->user->email,
                        ] : null,
                    ] : null,
                ] : null,

                'slip' => $slip ? [
                    'file_id' => $slip->id,
                    'original_name' => $slip->original_name,
                    'mime' => $slip->mime,
                    // ต้องมี route name files.show ใน routes/api.php หรือ routes/web.php
                    'url' => url("/api/v1/files/{$slip->id}"),
                ] : null,
            ];
        });

        return apiPaginate($payments);
    }

    public function approve(Request $request, int $paymentId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return apiResponse(null, 'Unauthenticated', 401);
        }

        /** @var Payment $payment */
        $payment = Payment::with('invoice')->findOrFail($paymentId);

        if ($payment->status !== 'waiting') {
            return apiResponse(null, 'This payment is already processed', 400);
        }

        return DB::transaction(function () use ($payment, $user) {

            // ใส่ verified fields + status
            $payment->status = 'approved';
            $payment->verified_by = (int) $user->id;
            $payment->verified_at = now();
            $payment->save();

            /** @var \App\Models\Invoice|null $invoice */
            $invoice = $payment->invoice;
            if ($invoice) {
                $invoice->status = 'paid';
                $invoice->save();
            }

            // refresh ให้ response ได้ค่าล่าสุด
            $payment->load('invoice');

            return apiResponse($payment, 'Payment approved successfully');
        });
    }

    public function reject(Request $request, int $paymentId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return apiResponse(null, 'Unauthenticated', 401);
        }

        /** @var Payment $payment */
        $payment = Payment::with('invoice')->findOrFail($paymentId);

        if ($payment->status !== 'waiting') {
            return apiResponse(null, 'This payment is already processed', 400);
        }

        return DB::transaction(function () use ($payment, $user) {

            $payment->status = 'rejected';
            $payment->verified_by = (int) $user->id;
            $payment->verified_at = now();
            $payment->save();

            /** @var \App\Models\Invoice|null $invoice */
            $invoice = $payment->invoice;
            if ($invoice) {
                // ถ้ามีใบอื่นที่ approve แล้ว ให้ยังเป็น paid ได้
                $hasApproved = $invoice->payments()
                    ->where('status', 'approved')
                    ->exists();

                $invoice->status = $hasApproved ? 'paid' : 'unpaid';
                $invoice->save();
            }

            $payment->load('invoice');

            return apiResponse($payment, 'Payment rejected successfully');
        });
    }
}