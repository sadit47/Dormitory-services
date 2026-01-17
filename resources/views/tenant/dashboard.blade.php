<x-tenant-layout>
  <x-slot name="title">Dashboard</x-slot>

  {{-- Header --}}
  <div class="flex items-start justify-between gap-4 mb-5">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">แดชบอร์ดผู้เช่า</h1>
      <p class="text-sm text-slate-500">ยินดีต้อนรับ, {{ auth()->user()->name }} 👋</p>

      <div class="mt-2 text-sm text-slate-600">
        ห้องปัจจุบัน:
        <span class="font-semibold text-slate-900">
          {{ optional($tenant->currentRoom)->code ?? '-' }}
        </span>
        <span class="mx-2 text-slate-300">•</span>
        สถานะบิลค้าง:
        <span class="font-semibold text-slate-900">{{ $unpaidCount }}</span> ใบ
      </div>
    </div>

    <div class="flex flex-wrap gap-2 justify-end">
      <a href="{{ route('tenant.invoices.index') }}"
        class="px-4 py-2 rounded-xl bg-indigo-700 text-white text-sm font-semibold hover:bg-indigo-600">
        🧾 ใบแจ้งหนี้/ใบเสร็จ
      </a>

      <a href="{{ route('tenant.repairs.create') }}"
        class="px-4 py-2 rounded-xl bg-indigo-700 text-white text-sm font-semibold hover:bg-indigo-600">
        🛠 แจ้งซ่อม + อัปโหลดรูป
      </a>

      <a href="{{ route('tenant.profile.edit') }}"
        class="px-4 py-2 rounded-xl bg-indigo-700 text-white text-sm font-semibold hover:bg-indigo-600">
        👤 ข้อมูลส่วนตัว
      </a>
    </div>
  </div>

  {{-- KPI Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="card p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">ยอดค้างชำระ</div>
          <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($unpaidTotal, 2) }}</div>
          <div class="text-xs text-slate-400 mt-2">จำนวนบิลค้าง: {{ $unpaidCount }}</div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-amber-50 grid place-items-center text-xl">💸</div>
      </div>
    </div>

    <div class="card p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">แจ้งซ่อมที่ยังดำเนินการ</div>
          <div class="text-2xl font-bold text-slate-900 mt-1">{{ $repairOpen }}</div>
          <div class="text-xs text-slate-400 mt-2">ติดตามสถานะในเมนู “แจ้งซ่อม”</div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 grid place-items-center text-xl">🛠</div>
      </div>
    </div>

    <div class="card p-5">

      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">ทำความสะอาดที่ยังดำเนินการ</div>
          <div class="text-2xl font-bold text-slate-900 mt-1">{{ $cleanOpen }}</div>
          <div class="text-xs text-slate-400 mt-2">รายการทำความสะอาด (กำลังทำ)</div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 grid place-items-center text-xl">🧹</div>
      </div>
    </div>

    <div class="card p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">ห้องของคุณ</div>
          <div class="text-2xl font-bold text-slate-900 mt-1">
            {{ optional($tenant->currentRoom)->code ?? '-' }}
          </div>
          <div class="text-xs text-slate-400 mt-2">ผู้เช่า: {{ auth()->user()->name }}</div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-slate-100 grid place-items-center text-xl">🏠</div>
      </div>
    </div>

  </div>

  {{-- Quick Actions --}}
<div class="bg-white rounded-2xl shadow-sm border p-5 mb-6">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-semibold text-slate-900">ทำรายการด่วน</h3>
    <div class="text-xs text-slate-400">กดแล้วไปหน้าใช้งานทันที</div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    {{-- 1) ใบแจ้งหนี้ --}}
    <a href="{{ route('tenant.invoices.index') }}"
       @class([
       "card-strong overflow-hidden block
              min-h-[120px] p-6
              flex flex-col items-center justify-center
              text-center",
        ])>
      <div class="font-semibold text-lg">🧾 ใบแจ้งหนี้/ใบเสร็จ</div>
      <div class="text-sm text-slate-500 mt-1">ดู PDF / ใบเสร็จ / รายการย้อนหลัง</div>
    </a>

    {{-- 2) ชำระบิลล่าสุด --}}
    @if(($recentUnpaid->first()))
      <a href="{{ route('tenant.payments.create', $recentUnpaid->first()) }}"
   @class([
     'card-strong overflow-hidden block min-h-[120px] p-6',
     'flex flex-col items-center justify-center text-center',
   ])>

        <div class="font-semibold text-lg">💸 ชำระบิลล่าสุด</div>
        <div class="text-sm text-slate-600 mt-1">
          อัปโหลดสลิปให้แอดมินตรวจสอบ (Invoice: {{ $recentUnpaid->first()->invoice_no }})
        </div>
      </a>
    @else
      <div class="p-6 rounded-2xl border bg-emerald-50
                  min-h-[120px]
                  flex flex-col items-center justify-center
                  text-center">
        <div class="font-semibold text-emerald-800 text-lg">✅ ไม่มีบิลค้าง</div>
        <div class="text-sm text-emerald-700 mt-1">ขอบคุณที่ชำระตรงเวลา</div>
      </div>
    @endif

    {{-- 3) แจ้งซ่อม --}}
    <a href="{{ route('tenant.repairs.create') }}"
       @class([
       "card-strong overflow-hidden block
              min-h-[120px] p-6
              flex flex-col items-center justify-center
              text-center",
   ])>
      <div class="font-semibold text-lg">🛠 แจ้งซ่อม + รูปภาพ</div>
      <div class="text-sm text-slate-600 mt-1">แนบรูป/รายละเอียดเพื่อให้ซ่อมได้เร็วขึ้น</div>
    </a>
  </div>
</div>


  {{-- Lists: Latest invoices + unpaid highlight --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    {{-- Latest invoices --}}
    <div class="card-strong overflow-hidden">
      <div class="p-5 flex items-center justify-between">
        <div>
          <div class="text-lg font-semibold text-slate-900">ใบแจ้งหนี้ล่าสุด</div>
          <div class="text-sm text-slate-500">ดู PDF / ใบเสร็จ / อัปโหลดสลิป</div>
        </div>
        <a href="{{ route('tenant.invoices.index') }}" class="rounded-xl bg-indigo-700 text-white px-4 py-2 text-sm font-semibold hover:bg-indigo-600">
          ดูทั้งหมด →
        </a>
      </div>

      <div class="overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-100 text-slate-700">
      <tr>
        <th class="text-left px-5 py-3 font-semibold">Invoice</th>
        <th class="text-left px-5 py-3 font-semibold">ประเภท</th>
        <th class="text-right px-5 py-3 font-semibold">ยอด</th>
        <th class="text-left px-5 py-3 font-semibold">สถานะ</th>
        <th class="text-right px-5 py-3 font-semibold w-[220px]">ทำรายการ</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-slate-200 bg-white">
      @forelse($latestInvoices as $inv)
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-4 font-semibold text-slate-900 whitespace-nowrap">
            {{ $inv->invoice_no }}
          </td>

          <td class="px-5 py-4 text-slate-700">
            {{ $inv->type }}
          </td>

          <td class="px-5 py-4 text-right text-slate-900 whitespace-nowrap">
            {{ number_format($inv->total,2) }}
          </td>

          <td class="px-5 py-4">
            @php
              $badge = match($inv->status){
                'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'partial' => 'bg-sky-50 text-sky-700 border-sky-200',
                'cancelled' => 'bg-slate-100 text-slate-700 border-slate-200',
                default => 'bg-amber-50 text-amber-700 border-amber-200',
              };
            @endphp

            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badge }}">
              {{ $inv->status }}
            </span>
          </td>

          {{-- ✅ ปุ่มไม่ซ้อนบรรทัด + เรียงเป็นแถว --}}
          <td class="px-5 py-3 text-right">
          <div class="inline-flex flex-wrap justify-end gap-2">

            @if($inv->status === 'paid' && $inv->receipt_no)
      <a class="text-xs px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
         href="{{ route('tenant.receipts.pdf', $inv) }}">Receipt</a>
    @endif

    @if(in_array($inv->status, ['unpaid','partial']))
      <a class="text-xs px-3 py-1.5 rounded-xl bg-indigo-700 text-white hover:bg-indigo-600"
         href="{{ route('tenant.payments.create', $inv) }}">อัปโหลดสลิป</a>
    @endif
  </div>
</td>
        </tr>
      @empty
        <tr>
          <td class="px-5 py-10 text-center text-slate-400" colspan="5">
            ยังไม่มีใบแจ้งหนี้
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

    </div>

    {{-- Unpaid highlight --}}
    <div class="card-strong overflow-hidden">
      <div class="p-5">
        <div class="text-lg font-semibold text-slate-900">บิลที่ค้างชำระ</div>
        <div class="text-sm text-slate-500">กด “ชำระ + อัปโหลดสลิป” เพื่อส่งให้แอดมินตรวจ</div>
      </div>

      <div class="px-5 pb-5 space-y-3">
        @forelse($recentUnpaid as $inv)
          <div class="border rounded-2xl p-4 flex items-center justify-between gap-3 hover:bg-slate-50">
            <div>
              <div class="font-semibold text-slate-900">{{ $inv->invoice_no }}</div>
              <div class="text-xs text-slate-500 mt-1">
                {{ strtoupper($inv->type) }} • งวด {{ $inv->period_month }}/{{ $inv->period_year }}
                @if($inv->due_date)
                  • ครบกำหนด {{ \Illuminate\Support\Carbon::parse($inv->due_date)->format('d/m/Y') }}
                @endif
              </div>
              <div class="text-sm mt-1">
                ยอด: <span class="font-semibold">{{ number_format($inv->total, 2) }}</span>
              </div>
            </div>

            <a href="{{ route('tenant.payments.create', $inv) }}"
              class="px-4 py-2 rounded-xl bg-indigo-700 text-white hover:bg-indigo-600 text-xs font-semibold whitespace-nowrap">
              ชำระ + อัปโหลดสลิป
            </a>
          </div>
        @empty
          <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4">
            ✅ ไม่มีบิลค้างชำระตอนนี้
          </div>
        @endforelse
      </div>
    </div>

  </div>

</x-tenant-layout>