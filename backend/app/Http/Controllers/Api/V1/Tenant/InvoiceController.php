<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// ✅ dompdf
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $u = $request->user();
        $tenant = Tenant::where('user_id', $u->id)->firstOrFail();

        $perPage = (int) $request->input('per_page', 10);

        $q = Invoice::query()
            ->where('tenant_id', $tenant->id)
            // ✅ โหลด payment ล่าสุดเพื่อทำ "กำลังดำเนินการ"
            ->with(['payments' => function ($p) {
                $p->select('id', 'invoice_id', 'status', 'created_at')
                  ->latest();
            }])
            ->latest();

        $page = $q->paginate($perPage);

        // ✅ เติม payment_status ให้แต่ละ invoice (waiting/approved/rejected/null)
        $page->getCollection()->transform(function ($inv) {
            $latestPay = $inv->payments->first();
            $inv->payment_status = $latestPay ? $latestPay->status : null;
            unset($inv->payments); // ไม่ต้องส่ง list payments ไป
            return $inv;
        });

        return apiPaginate($page);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
    $u = $request->user();
    $tenant = Tenant::where('user_id', $u->id)->firstOrFail();

    if ((int) $invoice->tenant_id !== (int) $tenant->id) {
        return apiResponse(null, 'Forbidden', 403);
        }

        $invoice->load([
            'items',
            'room',
            'payments' => function ($p) {
                $p->select('id', 'invoice_id', 'status', 'created_at')
                ->latest();
        }
        ]);

    $latestPay = $invoice->payments->first();
    $invoice->payment_status = $latestPay ? $latestPay->status : null;

    unset($invoice->payments);

    return apiResponse($invoice, 'OK');
    }

    // =========================================================
    // ✅ PDF (Tenant): เปิดดู/พิมพ์ใบแจ้งหนี้ + QR จ่ายเงิน
    // URL: GET /tenant/invoices/{invoice}/pdf
    // query ?download=1 จะดาวน์โหลดแทน stream
    // =========================================================
    public function pdf(Request $request, Invoice $invoice)
    {
        $u = $request->user();
        $tenant = Tenant::where('user_id', $u->id)->firstOrFail();

        // ✅ ป้องกัน tenant โหลดใบของคนอื่น
        if ((int) $invoice->tenant_id !== (int) $tenant->id) {
            return apiResponse(null, 'Forbidden', 403);
        }

        // โหลดข้อมูลเหมือนฝั่ง admin
        $invoice->load(['tenant.user', 'room', 'items']);

        // 1) QR รูปใน public/qr-code.jpg
        $qrPath = public_path('qr-code.jpg');
        $qrDataUri = null;
        if (is_file($qrPath)) {
            $ext = strtolower(pathinfo($qrPath, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
            $qrDataUri = "data:{$mime};base64," . base64_encode(file_get_contents($qrPath));
        }

        // 2) ฟอนต์ไทย
        $sarabunPath = public_path('fonts/THSarabunNew.ttf');
        $sarabunBoldPath = public_path('fonts/THSarabunNew Bold.ttf');

        $sarabunB64 = is_file($sarabunPath) ? base64_encode(file_get_contents($sarabunPath)) : '';
        $sarabunBoldB64 = is_file($sarabunBoldPath) ? base64_encode(file_get_contents($sarabunBoldPath)) : $sarabunB64;

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrDataUri' => $qrDataUri,
            'sarabunB64' => $sarabunB64,
            'sarabunBoldB64' => $sarabunBoldB64,
        ])->setPaper('a4');

        $filename = "invoice-{$invoice->invoice_no}.pdf";
        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}