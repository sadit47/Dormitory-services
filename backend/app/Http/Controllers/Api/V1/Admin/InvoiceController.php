<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, InvoiceItem, Tenant, Room};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// dompdf (ต้องติดตั้ง barryvdh/laravel-dompdf)
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));

        $invoices = Invoice::with([
                'tenant.user:id,name,email,phone',
                'room:id,code',
            ])
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where('invoice_no','like',"%{$q}%")
                   ->orWhereHas('tenant.user', fn($u)=>$u
                        ->where('name','like',"%{$q}%")
                        ->orWhere('email','like',"%{$q}%"));
            })
            ->when($type !== '', fn($qr)=>$qr->where('type',$type))
            ->when($status !== '', fn($qr)=>$qr->where('status',$status))
            ->latest()
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString();

        return apiPaginate($invoices);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['tenant.user', 'room', 'items', 'payments']);
        return apiResponse($invoice);
    }

    public function meta(): JsonResponse
    {
        $tenants = Tenant::with('user:id,name,email')->latest()->get();
        $rooms = Room::orderBy('code')->get(['id','code','floor','status']);

        return apiResponse([
            'tenants' => $tenants,
            'rooms' => $rooms,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required','exists:tenants,id'],
            'room_id' => ['nullable','exists:rooms,id'],
            'type' => ['required','in:rent,utility,repair,cleaning'],
            'period_month' => ['required','integer','min:1','max:12'],
            'period_year' => ['required','integer','min:2000','max:2100'],
            'due_date' => ['nullable','date'],
            'discount' => ['nullable','numeric','min:0'],
            'items' => ['required','array','min:1'],
            'items.*.description' => ['required','string','max:255'],
            'items.*.qty' => ['required','numeric','min:0.01'],
            'items.*.unit_price' => ['required','numeric','min:0'],
        ]);

        $discount = (float)($data['discount'] ?? 0);

        $invoice = DB::transaction(function () use ($data, $discount) {

            $invoiceNo = $this->genInvoiceNo(
                $data['type'],
                (int)$data['period_year'],
                (int)$data['period_month']
            );

            $invoice = Invoice::create([
                'invoice_no' => $invoiceNo,
                'tenant_id' => $data['tenant_id'],
                'room_id' => $data['room_id'] ?? null,
                'type' => $data['type'],
                'period_month' => $data['period_month'],
                'period_year' => $data['period_year'],
                'due_date' => $data['due_date'] ?? null,
                'status' => 'unpaid',
                'subtotal' => 0,
                'discount' => $discount,
                'total' => 0,
            ]);

            $subtotal = 0;
            foreach ($data['items'] as $it) {
                $amount = round((float)$it['qty'] * (float)$it['unit_price'], 2);
                $subtotal += $amount;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $it['description'],
                    'qty' => $it['qty'],
                    'unit_price' => $it['unit_price'],
                    'amount' => $amount,
                ]);
            }

            $invoice->update([
                'subtotal' => round($subtotal,2),
                'total' => max(0, round($subtotal - $discount, 2)),
            ]);

            return $invoice;
        });

        $invoice->load(['tenant.user', 'room', 'items']);
        return apiResponse($invoice, 'Created', 201);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status === 'paid') {
            return apiResponse(null, 'ใบนี้ชำระแล้ว ไม่อนุญาตให้แก้ไข', 409);
        }

        $data = $request->validate([
            'due_date' => ['nullable','date'],
            'discount' => ['nullable','numeric','min:0'],
            'items' => ['required','array','min:1'],
            'items.*.description' => ['required','string','max:255'],
            'items.*.qty' => ['required','numeric','min:0.01'],
            'items.*.unit_price' => ['required','numeric','min:0'],
        ]);

        DB::transaction(function () use ($invoice, $data) {

            $invoice->update([
                'due_date' => $data['due_date'] ?? null,
                'discount' => (float)($data['discount'] ?? 0),
            ]);

            $invoice->items()->delete();

            $subtotal = 0;
            foreach ($data['items'] as $it) {
                $amount = round((float)$it['qty'] * (float)$it['unit_price'], 2);
                $subtotal += $amount;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $it['description'],
                    'qty' => $it['qty'],
                    'unit_price' => $it['unit_price'],
                    'amount' => $amount,
                ]);
            }

            $invoice->update([
                'subtotal' => round($subtotal,2),
                'total' => max(0, round($subtotal - (float)$invoice->discount, 2)),
            ]);
        });

        $invoice->refresh()->load(['tenant.user','room','items']);
        return apiResponse($invoice, 'Updated');
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->status === 'paid') {
            return apiResponse(null, 'ใบนี้ชำระแล้ว ไม่อนุญาตให้ลบ', 409);
        }

        $invoice->delete();
        return apiResponse(null, 'Deleted');
    }

    // =========================================================
    // PDF: เปิดดู/พิมพ์ใบแจ้งหนี้ + QR จ่ายเงิน
    // URL: GET /admin/invoices/{invoice}/pdf
    // =========================================================
    public function pdf(Request $request, Invoice $invoice)
    {
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

    private function genInvoiceNo(string $type, int $year, int $month): string
    {
        $prefix = match ($type) {
            'rent' => 'RENT',
            'utility' => 'UTIL',
            'repair' => 'REPR',
            'cleaning' => 'CLEN',
        };

        $ym = sprintf('%04d%02d', $year, $month);
        $base = "INV-{$prefix}-{$ym}-";

        $last = Invoice::where('invoice_no','like',$base.'%')
            ->orderByDesc('invoice_no')
            ->value('invoice_no');

        $seq = $last ? ((int)substr($last, -4) + 1) : 1;

        return $base . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}