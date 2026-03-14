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

        if ((int) $invoice->tenant_id !== (int) $tenant->id) {
            return apiResponse(null, 'Forbidden', 403);
        }

        $invoice->load(['tenant.user', 'room', 'items']);

        $fontRegularPath = storage_path('fonts/NotoSansThai-Regular.ttf');
        $fontBoldPath = storage_path('fonts/NotoSansThai-Bold.ttf');

        $fontRegularB64 = is_file($fontRegularPath)
            ? base64_encode(file_get_contents($fontRegularPath))
            : '';

        $fontBoldB64 = is_file($fontBoldPath)
            ? base64_encode(file_get_contents($fontBoldPath))
            : $fontRegularB64;

        $qrPath = public_path('qr-code.jpg');
        $qrDataUri = null;

        if (is_file($qrPath)) {
            $ext = strtolower(pathinfo($qrPath, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
            $qrDataUri = "data:{$mime};base64," . base64_encode(file_get_contents($qrPath));
        }

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrDataUri' => $qrDataUri,
            'fontRegularB64' => $fontRegularB64,
            'fontBoldB64' => $fontBoldB64,
        ])->setPaper('a4', 'portrait');

        $filename = "invoice-{$invoice->invoice_no}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}