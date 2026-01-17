<x-tenant-layout>
  <x-slot name="title">ชำระเงิน + อัปโหลดสลิป</x-slot>

  <div class="card-strong overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold mb-1">อัปโหลดสลิปการโอน</h2>
        <p class="text-sm text-slate-500 mb-4">
          Invoice: <span class="font-semibold">{{ $invoice->invoice_no }}</span>
          • ยอดชำระ: <span class="font-semibold">{{ number_format($invoice->total, 2) }}</span>
        </p>

        <form method="POST" action="{{ route('tenant.payments.store', $invoice) }}"
              enctype="multipart/form-data" class="space-y-4">
          @csrf

          <div>
            <label class="text-sm text-slate-600">จำนวนเงินที่โอน</label>
            <input name="amount" type="number" step="0.01"
                   class="w-full mt-1 border rounded-xl px-3 py-2"
                   value="{{ old('amount', $invoice->total) }}" required>
            @error('amount') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="text-sm text-slate-600">วันที่/เวลาที่ชำระ</label>
            <input name="paid_at" type="datetime-local"
                   class="w-full mt-1 border rounded-xl px-3 py-2"
                   value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}"
                   required>
            @error('paid_at') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="text-sm text-slate-600">ไฟล์สลิป (jpg/png/pdf ไม่เกิน 5MB)</label>
            <input name="slip" type="file" accept=".jpg,.jpeg,.png,.pdf"
                   class="w-full mt-1 border rounded-xl px-3 py-2 bg-white" required>
            @error('slip') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="flex gap-2">
            <a href="{{ route('tenant.dashboard') }}"
               class="px-4 py-2 rounded-xl bg-amber-100 hover:bg-amber-200 text-sm font-semibold">
              กลับ
            </a>
            <button type="submit"
                    class="px-4 py-2 rounded-xl bg-indigo-700 text-white font-semibold">
              ส่งสลิปให้แอดมินตรวจสอบ
            </button>
          </div>
        </form>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h3 class="font-semibold mb-2">สถานะ Invoice</h3>
        <div class="text-sm text-slate-600 space-y-2">
          <div>ประเภท: <span class="font-semibold">{{ $invoice->type }}</span></div>
          <div>สถานะ: <span class="font-semibold">{{ $invoice->status }}</span></div>
          <div>กำหนดชำระ: <span class="font-semibold">{{ $invoice->due_date ?? '-' }}</span></div>
        </div>

        <hr class="my-4">

        <div class="flex gap-2">
          <a class="px-4 py-2 rounded-xl bg-indigo-700 text-white font-semibold"
             href="{{ route('tenant.invoices.pdf', $invoice) }}">ดู PDF</a>

          @if($invoice->status === 'paid' && $invoice->receipt_no)
            <a class="px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-sm"
               href="{{ route('tenant.receipts.pdf', $invoice) }}">ใบเสร็จ</a>
          @endif
        </div>
      </div>
    </div>
  </div>
</x-tenant-layout>
