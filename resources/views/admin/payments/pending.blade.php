{{-- resources/views/admin/payments/pending.blade.php --}}
<x-admin-layout title="ชำระเงินรอตรวจสอบ">

  <div class="card-strong p-5 mb-4">
    <form class="flex flex-col sm:flex-row gap-3" method="GET">
      <input name="q" value="{{ $q ?? '' }}"
             class="w-full rounded-xl border border-slate-300 px-4 py-2"
             placeholder="ค้นหา: เลขที่ใบแจ้งหนี้ / ชื่อ / อีเมล">
      <button class="px-5 py-2 rounded-xl bg-slate-900 text-white font-semibold">
        ค้นหา
      </button>
    </form>
  </div>

  @if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
      {{ session('success') }}
    </div>
  @endif

  @if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
      มีข้อผิดพลาด กรุณาตรวจสอบข้อมูล
    </div>
  @endif

  <div class="card-strong overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left px-5 py-3">Invoice</th>
            <th class="text-left px-5 py-3">ผู้เช่า</th>
            <th class="text-right px-5 py-3">ยอดโอน</th>
            <th class="text-left px-5 py-3">สลิป</th>
            <th class="text-left px-5 py-3">หมายเหตุ</th>
            <th class="text-right px-5 py-3">จัดการ</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          @forelse($payments as $p)
            @php
              $inv = $p->invoice;
              $tenantName = $inv?->tenant?->user?->name ?? '-';
              $tenantEmail = $inv?->tenant?->user?->email ?? '-';
              $slip = \App\Models\File::where('ref_type','payment')->where('ref_id',$p->id)->first();
            @endphp

            <tr class="hover:bg-slate-50 align-top">
              <td class="px-5 py-3 font-semibold text-slate-900">
                {{ $inv?->invoice_no ?? '-' }}
              </td>

              <td class="px-5 py-3">
                <div class="font-semibold text-slate-900">{{ $tenantName }}</div>
                <div class="text-xs text-slate-500">{{ $tenantEmail }}</div>
              </td>

              <td class="px-5 py-3 text-right font-semibold">
                {{ number_format($p->amount, 2) }}
              </td>

              <td class="px-5 py-3">
                @if($slip)
                  <a class="inline-flex items-center justify-center rounded-xl bg-indigo-600 text-white font-semibold px-4 py-2 text-sm hover:bg-indigo-500 transition"
                     target="_blank" href="{{ route('files.show',$slip) }}">
                    เปิดสลิป
                  </a>
                @else
                  <span class="text-slate-400">ไม่มีไฟล์</span>
                @endif
              </td>

              <td class="px-5 py-3">
                {{-- Approve --}}
                <form method="POST" action="{{ route('admin.payments.approve',$p) }}" class="flex flex-col gap-2">
                  @csrf
                  <input name="note"
                         class="w-72 rounded-xl border border-slate-300 px-3 py-2 text-sm"
                         placeholder="หมายเหตุ (ไม่บังคับ)">
                  @error('note') <div class="text-xs text-rose-600">{{ $message }}</div> @enderror

                  <div class="flex gap-2">
                    <button class="inline-flex items-center justify-center
                                  rounded-xl bg-emerald-600 text-white font-semibold
                                  px-5 py-2.5 text-sm hover:bg-emerald-500 transition">
                      อนุมัติ
                    </button>
                  </div>
                </form>

                {{-- Reject --}}
                <form method="POST" action="{{ route('admin.payments.reject',$p) }}" class="flex flex-col gap-2 mt-3"
                      onsubmit="return confirm('ปฏิเสธสลิปนี้?')">
                  @csrf
                  <input name="note"
                         class="w-72 rounded-xl border border-slate-300 px-3 py-2 text-sm"
                         placeholder="เหตุผล (บังคับ)" required>
                  <button class="inline-flex items-center justify-center
                                rounded-xl bg-rose-600 text-white font-semibold
                                px-5 py-2.5 text-sm hover:bg-rose-500 transition">
                    ปฏิเสธ
                  </button>
                </form>
              </td>

              <td class="px-5 py-3 text-right">
                <span class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold">
                  {{ $p->status }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                ไม่มีรายการรอตรวจสอบ
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-4">
      {{ $payments->links() }}
    </div>
  </div>

</x-admin-layout>
