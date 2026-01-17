<x-admin-layout title="ห้องพัก">

  {{-- ====== Top Controls ====== --}}
  <div class="flex flex-col gap-4">

    {{-- Row 1: Search + Filters + Add --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <form class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center" method="GET" action="{{ route('admin.rooms.index') }}">
        <input
          name="q"
          value="{{ $q }}"
          placeholder="ค้นหาเลขห้อง "
          class="w-full sm:w-72 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-tight
                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />

        <select
          name="type_id"
          class="w-full sm:w-auto min-w-[240px] rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-tight">
          <option value="">ทุกประเภท</option>
          @foreach($roomTypes as $t)
            <option value="{{ $t->id }}" @selected((string)$typeId === (string)$t->id)>{{ $t->name }}</option>
          @endforeach
        </select>

        <select
          name="status"
          class="w-full sm:w-auto min-w-[160px] rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-tight">
          <option value="">ทุกสถานะ</option>
          <option value="vacant" @selected($status==='vacant')>ว่าง</option>
          <option value="occupied" @selected($status==='occupied')>ไม่ว่าง</option>
          <option value="maintenance" @selected($status==='maintenance')>ซ่อมบำรุง</option>
        </select>

        <button
          class="w-full sm:w-auto rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
          ค้นหา
        </button>
      </form>

      <a href="{{ route('admin.rooms.create') }}"
         class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-4 text-sm font-semibold text-white hover:bg-indigo-500
                shadow-sm">
        + เพิ่มห้อง
      </a>
    </div>

    {{-- Row 2: Status Tabs (ไม่ให้เบียดกับ filter) --}}
    <div class="flex flex-wrap gap-2">
      @php
        $tabs = [
          '' => ['label' => 'ทั้งหมด', 'cls' => 'bg-slate-100 hover:bg-slate-200 text-slate-800'],
          'vacant' => ['label' => 'ว่าง', 'cls' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100'],
          'occupied' => ['label' => 'ไม่ว่าง', 'cls' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100'],
          'maintenance' => ['label' => 'ซ่อมบำรุง', 'cls' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100'],
        ];
      @endphp

      @foreach($tabs as $key => $tab)
        <a href="{{ route('admin.rooms.index', ['q' => $q, 'type_id' => $typeId, 'status' => $key]) }}"
           class="px-4 py-2 rounded-xl text-sm font-semibold
                  {{ (string)$status === (string)$key ? 'bg-indigo-600 text-white' : $tab['cls'] }}">
          {{ $tab['label'] }}
        </a>
      @endforeach
    </div>

  </div>

  {{-- ====== Cards ====== --}}
  <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($rooms as $room)
      @php
        $isVacant = $room->status === 'vacant';
        $isOcc    = $room->status === 'occupied';
        $isMain   = $room->status === 'maintenance';

        $statusText = $isVacant ? 'ว่าง' : ($isOcc ? 'ไม่ว่าง' : 'ซ่อมบำรุง');
        $barClass   = $isVacant ? 'bg-emerald-600' : ($isOcc ? 'bg-rose-600' : 'bg-amber-500');
        $pillClass  = $isVacant
          ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
          : ($isOcc
              ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
              : 'bg-amber-50 text-amber-800 ring-1 ring-amber-200');

        $tenant     = $room->activeAssignment?->tenant;
        $tenantId   = $tenant?->id;
        $tenantUser = $tenant?->user;
        $tenantName = $tenantUser?->name ?? $tenantUser?->email ?? '-';
      @endphp

      <div class="card-strong overflow-hidden">
  <div class="{{ $barClass }} h-2 rounded-t-2xl"></div>

        <div class="p-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-extrabold tracking-tight text-slate-900">{{ $room->code }}</h3>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $pillClass }}">
                  {{ $statusText }}
                </span>
              </div>

              <div class="mt-2 text-sm text-slate-700">
                ชั้น: <span class="font-semibold text-slate-900">{{ $room->floor }}</span>
                <span class="mx-2 text-slate-300">•</span>
                ประเภท: <span class="font-semibold text-slate-900">{{ $room->roomType?->name ?? '-' }}</span>
              </div>

              <div class="mt-1 text-sm text-slate-700">
                ราคา/เดือน:
                <span class="font-semibold text-slate-900">{{ number_format($room->price_monthly, 2) }}</span>
                <span class="text-slate-500">บาท</span>
              </div>
            </div>

            <div class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-800">
              ID #{{ $room->id }}
            </div>
          </div>

          <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="text-xs font-semibold text-slate-500">ผู้เช่าปัจจุบัน</div>
            <div class="mt-1 text-sm font-bold text-slate-900">
              {{ $isOcc ? $tenantName : '-' }}
            </div>
            @if($room->activeAssignment?->start_date)
              <div class="mt-1 text-xs text-slate-500">
                เข้าวันที่ {{ $room->activeAssignment->start_date->format('d/m/Y') }}
              </div>
            @endif
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            {{-- ดูผู้เช่า: ไป tenant ตรงๆ (ไม่ผ่าน rooms.tenant กันพัง) --}}
            <a href="{{ $tenantId ? route('admin.tenants.edit', $tenantId) : '#' }}"
               class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50
                      {{ $tenantId ? '' : 'opacity-50 pointer-events-none' }}">
              ดูผู้เช่า
            </a>

            <a href="{{ route('admin.rooms.edit', $room) }}"
               class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">
              แก้ไข
            </a>

            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}"
                  onsubmit="return confirm('ยืนยันลบห้อง {{ $room->code }} ?');">
              @csrf
              @method('DELETE')
              <button type="submit"
                class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-black">
                ลบ
              </button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-6 text-slate-700">
        ไม่พบข้อมูลห้องพัก
      </div>
    @endforelse
  </div>

  <div class="mt-6">
    {{ $rooms->links() }}
  </div>
</x-admin-layout>
