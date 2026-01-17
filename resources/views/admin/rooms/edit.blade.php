<x-admin-layout title="แก้ไขห้องพัก">
  <div class="max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('admin.rooms.update', $room) }}" class="space-y-4">
      @csrf
      @method('PUT')

      <div>
        <label class="text-sm font-semibold">เลขห้อง</label>
        <input name="code" value="{{ old('code', $room->code) }}"
               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
               required />
        @error('code') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">ชั้น</label>
          <input name="floor" type="number" min="1" value="{{ old('floor', $room->floor) }}"
                 class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                 required />
          @error('floor') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
  <label class="text-sm font-semibold">ประเภทห้อง</label>
  <select name="room_type_id"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
    <option value="">-- เลือกประเภท --</option>
    @foreach($roomTypes as $t)
      <option value="{{ $t->id }}"
        @selected(old('room_type_id', $room->room_type_id ?? null) == $t->id)>
        {{ $t->name }}
      </option>
    @endforeach
  </select>
  @error('room_type_id') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
</div>

      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">ราคา/เดือน</label>
          <input name="price_monthly" type="number" step="0.01" min="0" value="{{ old('price_monthly', $room->price_monthly) }}"
                 class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                 required />
          @error('price_monthly') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-semibold">สถานะ</label>
          <select name="status"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
            @foreach(['vacant' => 'ว่าง', 'occupied' => 'ไม่ว่าง', 'maintenance' => 'ซ่อมบำรุง'] as $k => $v)
              <option value="{{ $k }}" @selected(old('status', $room->status)===$k)>{{ $v }}</option>
            @endforeach
          </select>
          @error('status') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="flex gap-2">
        <a href="{{ route('admin.rooms.index') }}"
           class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">
          ย้อนกลับ
        </a>
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
          บันทึกการแก้ไข
        </button>
      </div>
    </form>
  </div>
</x-admin-layout>
