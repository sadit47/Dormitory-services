{{-- resources/views/admin/invoices/index.blade.php --}}
<x-admin-layout title="ใบแจ้งหนี้">
  <x-slot name="actions">
    <a href="{{ route('admin.invoices.create') }}"
       class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition">
      + ออกใบแจ้งหนี้
    </a>
  </x-slot>

  {{-- Filters --}}
  <div class="card-strong p-5 mb-4">
    <form class="grid grid-cols-1 md:grid-cols-4 gap-3" method="GET">
      <input name="q" value="{{ $q }}"
             class="w-full rounded-xl border border-slate-300 px-4 py-2"
             placeholder="ค้นหา: เลขที่ / ชื่อ / อีเมล">

      <select name="type" class="w-full rounded-xl border border-slate-300 px-4 py-2">
        <option value="">ทุกประเภท</option>
        <option value="rent"     @selected($type==='rent')>ค่าเช่า</option>
        <option value="utility"  @selected($type==='utility')>ค่าน้ำ/ค่าไฟ</option>
        <option value="repair"   @selected($type==='repair')>ค่าซ่อม</option>
        <option value="cleaning" @selected($type==='cleaning')>ค่าทำความสะอาด</option>
      </select>

      <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-2">
        <option value="">ทุกสถานะ</option>
        <option value="unpaid"    @selected($status==='unpaid')>ค้างชำระ</option>
        <option value="paid"      @selected($status==='paid')>ชำระแล้ว</option>
        <option value="cancelled" @selected($status==='cancelled')>ยกเลิก</option>
      </select>

      <div class="flex gap-2">
        <button class="flex-1 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition">
          ค้นหา
        </button>
        <a href="{{ route('admin.invoices.index') }}"
           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50 transition">
          ล้าง
        </a>
      </div>
    </form>
  </div>

  {{-- Table --}}
  <div class="card-strong overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left px-5 py-3">เลขที่</th>
            <th class="text-left px-5 py-3">ผู้เช่า</th>
            <th class="text-left px-5 py-3">ห้อง</th>
            <th class="text-left px-5 py-3">งวด</th>
            <th class="text-left px-5 py-3">ประเภท</th>
            <th class="text-right px-5 py-3">ยอดสุทธิ</th>
            <th class="text-left px-5 py-3">กำหนดชำระ</th>
            <th class="text-left px-5 py-3">สถานะ</th>
            <th class="text-right px-5 py-3">จัดการ</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          @forelse($invoices as $inv)
            @php
              $typeText = match($inv->type){
                'rent' => 'ค่าเช่า',
                'utility' => 'ค่าน้ำ/ค่าไฟ',
                'repair' => 'ค่าซ่อม',
                'cleaning' => 'ค่าทำความสะอาด',
                default => $inv->type
              };

              $typeBadge = match($inv->type){
                'rent' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'utility' => 'bg-sky-50 text-sky-700 border-sky-200',
                'repair' => 'bg-amber-50 text-amber-700 border-amber-200',
                'cleaning' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                default => 'bg-slate-50 text-slate-700 border-slate-200'
              };

              $statusText = match($inv->status){
                'unpaid' => 'ค้างชำระ',
                'paid' => 'ชำระแล้ว',
                'cancelled' => 'ยกเลิก',
                default => $inv->status
              };

              $statusBadge = match($inv->status){
                'unpaid' => 'bg-rose-50 text-rose-700 border-rose-200',
                'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'cancelled' => 'bg-slate-50 text-slate-600 border-slate-200',
                default => 'bg-slate-50 text-slate-700 border-slate-200'
              };

              $roomCode = $inv->room?->code ?? '-';
              $tenantName = $inv->tenant?->user?->name ?? '-';
              $tenantEmail = $inv->tenant?->user?->email ?? '';
              $period = sprintf('%02d/%04d', $inv->period_month, $inv->period_year);
              $due = $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') : '-';
            @endphp

            <tr class="hover:bg-slate-50">
              <td class="px-5 py-3 font-semibold text-slate-900">
                {{ $inv->invoice_no }}
              </td>

              <td class="px-5 py-3">
                <div class="font-semibold text-slate-900">{{ $tenantName }}</div>
                <div class="text-xs text-slate-500">{{ $tenantEmail }}</div>
              </td> 

              <td class="px-5 py-3">
                <span class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-900">
                  {{ $roomCode }}
                </span>
              </td>

              <td class="px-5 py-3">{{ $period }}</td>

              <td class="px-5 py-3">
                <span class="inline-flex items-center rounded-xl border px-3 py-1.5 font-semibold {{ $typeBadge }}">
                  {{ $typeText }}
                </span>
              </td>

              <td class="px-5 py-3 text-right font-semibold text-slate-900">
                {{ number_format($inv->total, 2) }}
              </td>

              <td class="px-5 py-3">{{ $due }}</td>

              <td class="px-5 py-3">
                <span class="inline-flex items-center rounded-xl border px-3 py-1.5 font-semibold {{ $statusBadge }}">
                  {{ $statusText }}
                </span>
              </td>

              <td class="px-5 py-3 text-right whitespace-nowrap">
                <div class="flex gap-2 justify-end items-center">
                  <a href="{{ route('admin.invoices.pdf', $inv) }}"
                     class="inline-flex items-center justify-center rounded-xl bg-slate-900 text-white font-semibold px-5 py-2.5 text-sm hover:bg-slate-800 transition">
                    PDF
                  </a>

                  <a href="{{ route('admin.invoices.edit', $inv) }}"
                     class="inline-flex items-center justify-center rounded-xl bg-indigo-600 text-white font-semibold px-5 py-2.5 text-sm hover:bg-indigo-500 transition">
                    แก้ไข
                  </a>

                  <form method="POST" action="{{ route('admin.invoices.destroy', $inv) }}"
                        class="inline-flex"
                        onsubmit="return confirm('ลบใบแจ้งหนี้นี้?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 text-white font-semibold px-5 py-2.5 text-sm hover:bg-rose-500 transition">
                      ลบ
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="px-5 py-10 text-center text-slate-500">
                ไม่พบข้อมูลใบแจ้งหนี้
              </td>
            </tr>
          @endforelse
        </tbody>

      </table>
    </div>

    <div class="p-4">
      {{ $invoices->links() }}
    </div>
  </div>
</x-admin-layout>
