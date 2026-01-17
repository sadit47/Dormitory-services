<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Payment, File};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function create(Request $request, Invoice $invoice)
    {
        $tenant = $request->user()->tenant;
        abort_if($invoice->tenant_id !== $tenant->id, 403);

        return view('tenant.payments.create', compact('invoice'));
    }

    public function store(Request $request, Invoice $invoice)
    {
        $tenant = $request->user()->tenant;
        abort_if($invoice->tenant_id !== $tenant->id, 403);

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

        return redirect()->route('tenant.dashboard')->with('success', 'อัปโหลดสลิปสำเร็จ รอแอดมินตรวจสอบ');
    }
}
