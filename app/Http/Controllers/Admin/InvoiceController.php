<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, InvoiceItem, Tenant, Room};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $type = $request->query('type');
        $status = $request->query('status');

        $invoices = Invoice::with(['tenant.user','room'])
            ->when($q, function ($qr) use ($q) {
                $qr->where('invoice_no','like',"%{$q}%")
                   ->orWhereHas('tenant.user', fn($u)=>$u->where('name','like',"%{$q}%")->orWhere('email','like',"%{$q}%"));
            })
            ->when($type, fn($qr)=>$qr->where('type',$type))
            ->when($status, fn($qr)=>$qr->where('status',$status))
            ->latest()->paginate(10)->withQueryString();

        return view('admin.invoices.index', compact('invoices','q','type','status'));
    }

    public function create()
    {
        $tenants = Tenant::with('user')->latest()->get();
        $rooms = Room::orderBy('code')->get();
        return view('admin.invoices.create', compact('tenants','rooms'));
    }

    public function store(Request $request)
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
            $invoiceNo = $this->genInvoiceNo($data['type'], $data['period_year'], $data['period_month']);

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

            $total = max(0, round($subtotal - $discount, 2));

            $invoice->update([
                'subtotal' => round($subtotal,2),
                'total' => $total,
            ]);

            return $invoice;
        });

        return redirect()->route('admin.invoices.index')->with('success','สร้างใบแจ้งหนี้สำเร็จ');
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['tenant.user','room','items']);
        return view('admin.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        // ป้องกันแก้ invoice ที่ paid แล้ว (กันข้อมูลเพี้ยน)
        if ($invoice->status === 'paid') {
            return back()->withErrors(['status'=>'ใบนี้ชำระแล้ว ไม่อนุญาตให้แก้ไข']);
        }

        $data = $request->validate([
            'due_date' => ['nullable','date'],
            'discount' => ['nullable','numeric','min:0'],
            'items' => ['required','array','min:1'],
            'items.*.id' => ['nullable','integer'],
            'items.*.description' => ['required','string','max:255'],
            'items.*.qty' => ['required','numeric','min:0.01'],
            'items.*.unit_price' => ['required','numeric','min:0'],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'due_date' => $data['due_date'] ?? null,
                'discount' => (float)($data['discount'] ?? 0),
            ]);

            // ลบของเดิมแล้วสร้างใหม่ (ง่ายและกันเพี้ยน)
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

            $total = max(0, round($subtotal - (float)$invoice->discount, 2));
            $invoice->update(['subtotal'=>round($subtotal,2),'total'=>$total]);
        });

        return redirect()->route('admin.invoices.index')->with('success','แก้ไขใบแจ้งหนี้สำเร็จ');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->withErrors(['status'=>'ใบนี้ชำระแล้ว ไม่อนุญาตให้ลบ']);
        }
        $invoice->delete();
        return back()->with('success','ลบใบแจ้งหนี้สำเร็จ');
    }

    public function pdfInvoice(Invoice $invoice)
{
    $invoice->load(['tenant.user','room','items','payments']);

    // QR
    $qrDataUri = null;
    $qrPath = public_path('qr-code.jpg');
    if (file_exists($qrPath)) {
        $qrDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($qrPath));
    }

    // Thai font (Sarabun) -> embed as base64 (กันโหลดไม่เจอ)
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

    private function genInvoiceNo(string $type, int $year, int $month): string
    {
        // เช่น INV-RENT-202601-0001
        $prefix = match ($type) {
            'rent' => 'RENT',
            'utility' => 'UTIL',
            'repair' => 'REPR',
            'cleaning' => 'CLEN',
        };

        $ym = sprintf('%04d%02d', $year, $month);
        $base = "INV-{$prefix}-{$ym}-";

        $last = Invoice::where('invoice_no','like',$base.'%')->orderByDesc('invoice_no')->value('invoice_no');
        $seq = $last ? ((int)substr($last, -4) + 1) : 1;

        return $base . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}
