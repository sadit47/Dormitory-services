{{-- resources/views/admin/repairs/index.blade.php --}}
<x-admin-layout title="แจ้งซ่อม">

  <div class="card-strong p-5 mb-4">
    <form class="grid grid-cols-1 md:grid-cols-3 gap-3" method="GET">
      <input name="q" value="{{ $q }}"
             class="w-full rounded-xl border border-slate-300 px-4 py-2"
             placeholder="ค้นหา: หัวข้อ / ชื่อผู้เช่า / อีเมล / ห้อง">

      <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-2">
        <option value="">ทุกสถานะ</option>
        <option value="submitted"   @selected(($status ?? '')==='submitted')>submitted</option>
        <option value="in_progress" @selected(($status ?? '')==='in_progress')>in_progress</option>
        <option value="done"        @selected(($status ?? '')==='done')>done</option>
        <option value="rejected"    @selected(($status ?? '')==='rejected')>rejected</option>
      </select>

      <div class="flex gap-2">
        <button class="w-full rounded-xl bg-slate-900 text-white font-semibold px-5 py-2">
          ค้นหา
        </button>
        <a href="{{ route('admin.repairs.index') }}"
           class="rounded-xl border border-slate-300 bg-white px-5 py-2 font-semibold hover:bg-slate-50">
          ล้าง
        </a>
      </div>
    </form>
  </div>

  <div class="card-strong overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left px-5 py-3">หัวข้อ</th>
            <th class="text-left px-5 py-3">ผู้เช่า</th>
            <th class="text-left px-5 py-3">ห้อง</th>
            <th class="text-left px-5 py-3">ความเร่งด่วน</th>
            <th class="text-left px-5 py-3">สถานะ</th>
            <th class="text-left px-5 py-3">รูป</th>
            <th class="text-right px-5 py-3">จัดการ</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          @forelse($repairs as $r)
            @php
              $tenantName  = $r->tenant?->user?->name ?? '-';
              $tenantEmail = $r->tenant?->user?->email ?? '-';
              $roomCode    = $r->room?->code ?? '-';
              $img         = $r->files->first(); // รูปแรก
            @endphp

            <tr class="hover:bg-slate-50 align-top">
              <td class="px-5 py-3">
                <div class="font-semibold text-slate-900">{{ $r->title }}</div>
                <div class="text-xs text-slate-500 mt-1">
                  {{ $r->requested_at?->format('d/m/Y H:i') ?? '' }}
                </div>
                @if($r->description)
                  <div class="text-xs text-slate-600 mt-2 whitespace-pre-wrap">{{ $r->description }}</div>
                @endif
              </td>

              <td class="px-5 py-3">
                <div class="font-semibold">{{ $tenantName }}</div>
                <div class="text-xs text-slate-500">{{ $tenantEmail }}</div>
              </td>

              <td class="px-5 py-3">
                <span class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-900">
                  {{ $roomCode }}
                </span>
              </td>

              <td class="px-5 py-3">
                <span class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold">
                  {{ $r->priority }}
                </span>
              </td>

              <td class="px-5 py-3">
                <span class="inline-flex items-center rounded-xl px-3 py-1.5 text-xs font-semibold
                  {{ $r->status==='submitted' ? 'bg-amber-100 text-amber-800' : '' }}
                  {{ $r->status==='in_progress' ? 'bg-indigo-100 text-indigo-800' : '' }}
                  {{ $r->status==='done' ? 'bg-emerald-100 text-emerald-800' : '' }}
                  {{ $r->status==='rejected' ? 'bg-rose-100 text-rose-800' : '' }}
                ">
                  {{ $r->status }}
                </span>
              </td>

              <td class="px-5 py-3">
                @if($img)
                  <a class="inline-flex items-center justify-center rounded-xl bg-slate-900 text-white font-semibold px-4 py-2 text-sm hover:bg-slate-800 transition"
                     target="_blank" href="{{ route('files.show', $img) }}">
                    เปิดรูป
                  </a>
                @else
                  <span class="text-slate-400">ไม่มี</span>
                @endif
              </td>

              <td class="px-5 py-3 text-right whitespace-nowrap">
                <div class="inline-flex gap-2 justify-end items-center">

                  {{-- อัปเดตสถานะ --}}
                  <form method="POST" action="{{ route('admin.repairs.status', $r) }}" class="inline-flex gap-2 items-center">
                    @csrf
                    @method('PATCH')

                    <select name="status" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                      <option value="submitted"   @selected($r->status==='submitted')>submitted</option>
                      <option value="in_progress" @selected($r->status==='in_progress')>in_progress</option>
                      <option value="done"        @selected($r->status==='done')>done</option>
                      <option value="rejected"    @selected($r->status==='rejected')>rejected</option>
                    </select>

                    <button class="inline-flex items-center justify-center rounded-xl bg-indigo-600 text-white font-semibold px-4 py-2.5 text-sm hover:bg-indigo-500 transition">
                      บันทึก
                    </button>
                  </form>

                  {{-- ลบ --}}
                  <form method="POST" action="{{ route('admin.repairs.destroy', $r) }}"
                        onsubmit="return confirm('ยืนยันลบรายการแจ้งซ่อมนี้? รูปที่แนบจะถูกลบด้วย');"
                        class="inline-flex">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 text-white font-semibold px-4 py-2.5 text-sm hover:bg-rose-500 transition">
                      ลบ
                    </button>
                  </form>

                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                ไม่มีรายการแจ้งซ่อม
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-4">
      {{ $repairs->links() }}
    </div>
  </div>

</x-admin-layout>
