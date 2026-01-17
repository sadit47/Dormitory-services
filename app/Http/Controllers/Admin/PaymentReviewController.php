<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Payment, Invoice};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentReviewController extends Controller
{
    public function pending(Request $request)
    {
        $q = $request->query('q');

        $payments = Payment::with(['invoice.tenant.user'])
            // กันเคสสถานะไม่ตรง (บางคนตั้ง pending บางคนตั้ง waiting)
            ->whereIn('status', ['waiting'])
            ->when($q, function ($qr) use ($q) {
                $qr->whereHas('invoice', function ($inv) use ($q) {
                    $inv->where('invoice_no', 'like', "%{$q}%")
                        ->orWhereHas('tenant.user', function ($u) use ($q) {
                            $u->where('name', 'like', "%{$q}%")
                              ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.payments.pending', compact('payments', 'q'));
    }

    public function approve(Request $request, Payment $payment)
    {
        $request->validate(['note' => ['nullable','string','max:255']]);

        DB::transaction(function () use ($payment, $request) {
            $payment->update([
                'status' => 'approved',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'note' => $request->input('note'),
            ]);

            $invoice = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);

            $approvedSum = $invoice->payments()
                ->where('status','approved')
                ->sum('amount');

            if ($approvedSum >= $invoice->total) {
                if ($invoice->status !== 'paid') {
                    $invoice->update([
                        'status' => 'paid',
                        'receipt_no' => $this->genReceiptNo(),
                        'receipt_issued_at' => now(),
                    ]);
                }
            } elseif ($approvedSum > 0) {
                $invoice->update(['status' => 'partial']);
            } else {
                $invoice->update(['status' => 'unpaid']);
            }
        });

        return back()->with('success','อนุมัติสลิปเรียบร้อย');
    }

    public function reject(Request $request, Payment $payment)
    {
        $request->validate(['note' => ['required','string','max:255']]);

        $payment->update([
            'status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'note' => $request->input('note'),
        ]);

        return back()->with('success','ปฏิเสธสลิปเรียบร้อย');
    }

    private function genReceiptNo(): string
    {
        $ym = now()->format('Ym');
        $base = "RCP-{$ym}-";

        $last = Invoice::whereNotNull('receipt_no')
            ->where('receipt_no','like',$base.'%')
            ->orderByDesc('receipt_no')
            ->value('receipt_no');

        $seq = $last ? ((int)substr($last, -4) + 1) : 1;

        return $base . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}
