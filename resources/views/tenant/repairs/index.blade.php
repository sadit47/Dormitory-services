<x-tenant-layout>
  <x-slot name="title">แจ้งซ่อม</x-slot>

  <div class="flex items-start justify-between gap-4 mb-5">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">รายการแจ้งซ่อม</h1>
      <p class="text-sm text-slate-500">ติดตามสถานะการแจ้งซ่อมของคุณ</p>
    </div>

    <a href="{{ route('tenant.repairs.create') }}"
       class="px-4 py-2 rounded-xl bg-indigo-700 text-white text-sm font-semibold hover:bg-indigo-600">
      🛠 แจ้งซ่อมใหม่
    </a>
  </div>

  <div class="card-strong overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left px-5 py-3">วันที่</th>
            <th class="text-left px-5 py-3">หัวข้อ</th>
            <th class="text-left px-5 py-3">รายละเอียด</th>
            <th class="text-left px-5 py-3">สถานะ</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($repairs as $r)
            <tr class="hover:bg-slate-50">
              <td class="px-5 py-3 whitespace-nowrap text-slate-700">
                {{ optional($r->created_at)->format('d/m/Y H:i') }}
              </td>
              <td class="px-5 py-3 font-semibold text-slate-900">
                {{ $r->title }}
              </td>
              <td class="px-5 py-3 text-slate-700">
                {{ $r->description }}
              </td>
              <td class="px-5 py-3">
                @php
                  $badge = match($r->status){
                    'done' => 'bg-emerald-50 text-emerald-700',
                    'in_progress' => 'bg-sky-50 text-sky-700',
                    'cancelled' => 'bg-slate-100 text-slate-700',
                    default => 'bg-amber-50 text-amber-700',
                  };
                @endphp
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                  {{ $r->status }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td class="px-5 py-10 text-center text-slate-400" colspan="4">
                ยังไม่มีรายการแจ้งซ่อม
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</x-tenant-layout>
