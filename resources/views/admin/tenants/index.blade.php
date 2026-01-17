{{-- resources/views/admin/tenants/index.blade.php --}}
<x-admin-layout title="ผู้เช่า">
  <x-slot name="actions">
    <a href="{{ \Illuminate\Support\Facades\Route::has('admin.tenants.create') ? route('admin.tenants.create') : route('tenants.create') }}"
       class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
      + เพิ่มผู้เช่า
    </a>
  </x-slot>

  {{-- Search --}}
  <div class="card-strong p-5 mb-4">
    <form class="flex flex-col sm:flex-row gap-3" method="GET">
      <input name="q" value="{{ $q }}"
             class="w-full rounded-xl border border-slate-300 px-4 py-2"
             placeholder="ค้นหา: ชื่อ / อีเมล / เบอร์ / บัตรประชาชน">
      <button class="px-5 py-2 rounded-xl bg-slate-900 text-white font-semibold">
        ค้นหา
      </button>
    </form>
  </div>

  {{-- Table --}}
  <div class="card-strong overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left px-5 py-3">ชื่อ</th>
            <th class="text-left px-5 py-3">ห้อง</th>
            <th class="text-left px-5 py-3">อีเมล</th>
            <th class="text-left px-5 py-3">รหัสผ่าน</th>
            <th class="text-left px-5 py-3">โทร</th>
            <th class="text-left px-5 py-3">Citizen ID</th>
            <th class="text-right px-5 py-3">จัดการ</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          @forelse($tenants as $t)
            @php
              // ===== Room =====
              $roomCode = $t->currentRoom?->code ?? '-';

              // ===== Password (decrypt safely) =====
              $plainPw = null;
              try {
                  if (!empty($t->user?->admin_password_enc)) {
                      $plainPw = \Illuminate\Support\Facades\Crypt::decryptString($t->user->admin_password_enc);
                  }
              } catch (\Throwable $e) {
                  $plainPw = null;
              }

              // ===== Copy text =====
              $copyText = "ห้อง: {$roomCode}\n"
                        . "อีเมล: " . ($t->user->email ?? '-') . "\n"
                        . "รหัสผ่าน: " . ($plainPw ?? '-');

              // ===== Routes (รองรับทั้ง admin.* และไม่มี admin.) =====
              $editRoute = \Illuminate\Support\Facades\Route::has('admin.tenants.edit')
                  ? route('admin.tenants.edit', $t)
                  : route('tenants.edit', $t);

              $destroyRoute = \Illuminate\Support\Facades\Route::has('admin.tenants.destroy')
                  ? route('admin.tenants.destroy', $t)
                  : route('tenants.destroy', $t);
            @endphp

            <tr class="hover:bg-slate-50">
              <td class="px-5 py-3 font-semibold text-slate-900">
                {{ $t->user->name ?? '-' }}
              </td>

              <td class="px-5 py-3">
                <span class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-900">
                  {{ $roomCode }}
                </span>
              </td>

              <td class="px-5 py-3">{{ $t->user->email ?? '-' }}</td>

              <td class="px-5 py-3">
                @if($plainPw)
                  <span class="font-mono font-semibold text-slate-900">{{ $plainPw }}</span>
                @else
                  <span class="text-slate-400">-</span>
                @endif
              </td>

              <td class="px-5 py-3">{{ $t->user->phone ?? '-' }}</td>
              <td class="px-5 py-3">{{ $t->citizen_id ?? '-' }}</td>

              <td class="px-5 py-3 text-right whitespace-nowrap min-w-[320px]">
  <div class="inline-flex gap-2 justify-end items-center">

    {{-- Copy --}}
    <button type="button"
            class="inline-flex items-center justify-center
                   rounded-xl bg-slate-900 text-white font-semibold
                   px-5 py-2.5 text-sm
                   hover:bg-slate-800 transition"
            onclick="navigator.clipboard.writeText(@js($copyText));
                     const b=this; b.innerText='คัดลอกแล้ว';
                     setTimeout(()=>b.innerText='คัดลอก',1200);">
      คัดลอก
    </button>

    {{-- Edit --}}
    <a href="{{ $editRoute }}"
       class="inline-flex items-center justify-center
              rounded-xl bg-indigo-600 text-white font-semibold
              px-5 py-2.5 text-sm
              hover:bg-indigo-500 transition">
      แก้ไข
    </a>

    {{-- Delete --}}
    <form method="POST"
          action="{{ $destroyRoute }}"
          class="inline-flex"
          onsubmit="return confirm('ลบผู้เช่านี้?')">
      @csrf
      @method('DELETE')
      <button type="submit"
              class="inline-flex items-center justify-center
                     rounded-xl bg-red-600 text-white font-semibold
                     px-5 py-2.5 text-sm
                     hover:bg-red-500 transition">
        ลบ
      </button>
    </form>

  </div>
</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                ไม่พบข้อมูลผู้เช่า
              </td>
            </tr>
          @endforelse
        </tbody>

      </table>
    </div>

    <div class="p-4">
      {{ $tenants->links() }}
    </div>
  </div>
</x-admin-layout>
