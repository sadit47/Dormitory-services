<x-tenant-layout>
  <x-slot name="title">ใบแจ้งหนี้ / ใบเสร็จ</x-slot>

  <div class="card-strong overflow-hidden">
  <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="p-5 flex items-center justify-between">
      <div>
        <div class="text-lg font-semibold">รายการใบแจ้งหนี้</div>
        <div class="text-sm text-slate-500">ดู PDF / ใบเสร็จ และชำระเงินพร้อมอัปโหลดสลิป</div>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left px-5 py-3">Invoice</th>
            <th class="text-left px-5 py-3">ประเภท</th>
            <th class="text-right px-5 py-3">ยอด</th>
            <th class="text-left px-5 py-3">สถานะ</th>
            <th class="text-left px-5 py-3">กำหนดชำระ</th>
            <th class="text-right px-5 py-3">การทำรายการ</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($invoices as $inv)
            <tr class="hover:bg-slate-50">
              <td class="px-5 py-3 font-semibold">{{ $inv->invoice_no }}</td>
              <td class="px-5 py-3">{{ $inv->type }}</td>
              <td class="px-5 py-3 text-right">{{ number_format($inv->total, 2) }}</td>
              <td class="px-5 py-3">
                <span class="px-2 py-1 rounded-full text-xs
                  {{ $inv->status==='paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                  {{ $inv->status }}
                </span>
              </td>
              <td class="px-5 py-3">{{ $inv->due_date ?? '-' }}</td>
              <td class="px-5 py-3 text-right space-x-2">
                <a class="px-3 py-1.5 rounded-xl bg-slate-100"
                   href="{{ route('tenant.invoices.pdf', $inv) }}">PDF</a>

                @if($inv->status === 'paid' && $inv->receipt_no)
                  <a class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700"
                     href="{{ route('tenant.receipts.pdf', $inv) }}">Receipt</a>
                @endif

                @if(in_array($inv->status, ['unpaid','partial']))
                  <a class="px-3 py-1.5 rounded-xl bg-indigo-700 text-white"
                     href="{{ route('tenant.payments.create', $inv) }}">ชำระเงิน</a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td class="px-5 py-8 text-center text-slate-400" colspan="6">ยังไม่มีใบแจ้งหนี้</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-4">
      {{ $invoices->links() }}
    </div>
  </div>
  </div>
</x-tenant-layout>
