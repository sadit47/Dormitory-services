<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Admin UI controller (Blade only)
 * Data CRUD must be done via REST API.
 * PDF streams remain here (download action, not CRUD).
 */
class InvoiceController extends Controller
{
    public function index()
    {
        return view('admin.invoices.index');
    }

    public function create()
    {
        return view('admin.invoices.create');
    }

    public function edit(Invoice $invoice)
    {
        return view('admin.invoices.edit', ['invoiceId' => $invoice->id]);
    }

    // Legacy web CRUD disabled
    public function store(): void { abort(404); }
    public function update(): void { abort(404); }
    public function destroy(): void { abort(404); }

    public function pdfInvoice(Invoice $invoice)
    {
        $invoice->load(['tenant.user','room','items','payments']);

        $qrDataUri = null;
        $qrPath = public_path('qr-code.jpg');
        if (file_exists($qrPath)) {
            $qrDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($qrPath));
        }

        $sarabunB64 = null;
        $fontPath = public_path('fonts/THSarabunNew.ttf');
        if (file_exists($fontPath)) {
            $sarabunB64 = base64_encode(file_get_contents($fontPath));
        }
        $fontBoldPath = public_path('fonts/THSarabunNew-Bold.ttf');
        $sarabunBoldB64 = file_exists($fontBoldPath) ? base64_encode(file_get_contents($fontBoldPath)) : null;

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrDataUri' => $qrDataUri,
            'sarabunB64' => $sarabunB64,
            'sarabunBoldB64' => $sarabunBoldB64,
        ])->setPaper('a4');

        return $pdf->stream("invoice-{$invoice->invoice_no}.pdf");
    }

    public function pdfReceipt(Invoice $invoice)
    {
        abort_if($invoice->status !== 'paid' || !$invoice->receipt_no, 404);

        $invoice->load(['tenant.user','room','items','payments']);

        $qrDataUri = null;
        $qrPath = public_path('qr-code.jpg');
        if (file_exists($qrPath)) {
            $qrDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($qrPath));
        }

        $sarabunB64 = null;
        $fontPath = public_path('fonts/THSarabunNew.ttf');
        if (file_exists($fontPath)) {
            $sarabunB64 = base64_encode(file_get_contents($fontPath));
        }
        $fontBoldPath = public_path('fonts/THSarabunNew-Bold.ttf');
        $sarabunBoldB64 = file_exists($fontBoldPath) ? base64_encode(file_get_contents($fontBoldPath)) : null;

        $pdf = Pdf::loadView('pdf.receipt', [
            'invoice' => $invoice,
            'qrDataUri' => $qrDataUri,
            'sarabunB64' => $sarabunB64,
            'sarabunBoldB64' => $sarabunBoldB64,
        ])->setPaper('a4');

        return $pdf->stream("receipt-{$invoice->receipt_no}.pdf");
    }
}
