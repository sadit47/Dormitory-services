<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TenantInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant?->id;

        $invoices = Invoice::with(['room'])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->paginate(10);

        return view('tenant.invoices.index', compact('invoices'));
    }

    public function pdf(Invoice $invoice)
    {
        // กันเปิดข้ามคน
        $this->abortIfNotOwner($invoice);

        $invoice->load(['tenant.user','room','items','payments']);

        // QR
        $qrDataUri = null;
        $qrPath = public_path('qr-code.jpg'); // public/qr-code.jpg
        if (file_exists($qrPath)) {
            $qrDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($qrPath));
        }

        // Thai font (Sarabun) -> embed base64
        $sarabunB64 = null;
        $fontPath = public_path('fonts/THSarabunNew.ttf'); // public/fonts/THSarabunNew.ttf
        if (file_exists($fontPath)) {
            $sarabunB64 = base64_encode(file_get_contents($fontPath));
        }

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'   => $invoice,
            'qrDataUri' => $qrDataUri,
            'sarabunB64'=> $sarabunB64,
        ])->setPaper('a4');

        return $pdf->stream("invoice-{$invoice->invoice_no}.pdf");
    }

    public function receiptPdf(Invoice $invoice)
    {
        // กันเปิดข้ามคน
        $this->abortIfNotOwner($invoice);

        abort_if($invoice->status !== 'paid' || !$invoice->receipt_no, 404);

        $invoice->load(['tenant.user','room','items','payments']);

        // QR
        $qrDataUri = null;
        $qrPath = public_path('qr-code.jpg');
        if (file_exists($qrPath)) {
            $qrDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($qrPath));
        }

        // Thai font
        $sarabunB64 = null;
        $fontPath = public_path('fonts/THSarabunNew.ttf');
        if (file_exists($fontPath)) {
            $sarabunB64 = base64_encode(file_get_contents($fontPath));
        }

        $pdf = Pdf::loadView('pdf.receipt', [
            'invoice'   => $invoice,
            'qrDataUri' => $qrDataUri,
            'sarabunB64'=> $sarabunB64,
        ])->setPaper('a4');

        return $pdf->stream("receipt-{$invoice->receipt_no}.pdf");
    }

    private function abortIfNotOwner(Invoice $invoice): void
    {
        $tenantId = auth()->user()->tenant?->id;
        abort_if(!$tenantId || $invoice->tenant_id !== $tenantId, 403);
    }
}
