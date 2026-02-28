<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function create(Invoice $invoice)
    {
        $user = Auth::user();

        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        if ((int)$invoice->tenant_id !== (int)$tenant->id) {
            abort(403);
        }

        return view('tenant.payments.create', compact('invoice'));
    }

    public function store(Request $request, Invoice $invoice)
    {
        $user = Auth::user();

        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        if ((int)$invoice->tenant_id !== (int)$tenant->id) {
            abort(403);
        }

        $data = $request->validate([
            'amount'  => ['required', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'slip'    => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);

        /* -------------------------------------------------
         | 1) สร้าง payment (ให้ตรง schema DB)
         * ------------------------------------------------- */
        $payment = Payment::create([
            'invoice_id'    => $invoice->id,
            'payer_user_id' => $user->id,        // ✅ REQUIRED
            'amount'        => $data['amount'],
            'method'        => 'transfer',       // ✅ REQUIRED
            'paid_at'       => $data['paid_at'],
            'status'        => 'waiting',        // ✅ enum ถูกต้อง
        ]);

        /* -------------------------------------------------
         | 2) เก็บไฟล์สลิป
         * ------------------------------------------------- */
        $file = $request->file('slip');

        $disk = 'private';
        $path = $file->store('payments', $disk);

        File::create([
            'owner_user_id' => $user->id,
            'ref_type'      => 'payment_slip',
            'ref_id'        => $payment->id,
            'disk'          => $disk,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $file->getClientMimeType(),
            'size'          => $file->getSize(),
        ]);

        return redirect()
            ->route('tenant.invoices.index')
            ->with('success', 'ส่งหลักฐานการชำระเงินเรียบร้อย รอเจ้าหน้าที่ตรวจสอบ');
    }
}
