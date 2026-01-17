<x-admin-layout title="เพิ่มผู้เช่า">
  <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-sm text-slate-500">สร้างผู้เช่าใหม่</div>
        <div class="mt-1 text-lg font-bold text-slate-900">เพิ่มผู้เช่า</div>
      </div>

      <a href="{{ route('admin.tenants.index') }}"
         class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">
        ย้อนกลับ
      </a>
    </div>

    <form method="POST" action="{{ route('admin.tenants.store') }}" class="mt-6 space-y-4">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">ชื่อ</label>
          <input name="name" value="{{ old('name') }}"
                 class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          @error('name') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-semibold">อีเมล</label>
          <input name="email" value="{{ old('email') }}"
                 class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          @error('email') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>
      </div>

      <div>
        <label class="text-sm font-semibold">รหัสผ่าน</label>
        <input type="password" name="password"
               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
        @error('password') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">โทร</label>
          <input name="phone" value="{{ old('phone') }}"
                 class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="text-sm font-semibold">Citizen ID</label>
          <input name="citizen_id" value="{{ old('citizen_id') }}"
                 class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          @error('citizen_id') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>
      </div>

      <div>
        <label class="text-sm font-semibold">ที่อยู่</label>
        <textarea name="address" rows="3"
                  class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('address') }}</textarea>
        @error('address') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
      </div>

      <div>
        <label class="text-sm font-semibold">ผู้ติดต่อฉุกเฉิน</label>
        <input name="emergency_contact" value="{{ old('emergency_contact') }}"
               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
        @error('emergency_contact') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
      </div>

      <div>
        <label class="text-sm font-semibold">ห้องที่พัก (ห้องว่าง)</label>
        <select name="room_id"
                class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
          <option value="">-- ยังไม่เลือกห้อง --</option>
          @foreach($vacantRooms as $r)
            <option value="{{ $r->id }}" @selected(old('room_id') == $r->id)>
              {{ $r->code }} (ชั้น {{ $r->floor }})
            </option>
          @endforeach
        </select>
        @error('room_id') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">เริ่มพัก</label>
          <input type="date" name="start_date" value="{{ old('start_date') }}"
                 class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          @error('start_date') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-semibold">สิ้นสุด</label>
          <input type="date" name="end_date" value="{{ old('end_date') }}"
                 class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          @error('end_date') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="flex gap-2 pt-2">
        <a href="{{ route('admin.tenants.index') }}"
           class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
          ยกเลิก
        </a>
        <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
          บันทึก
        </button>
      </div>
    </form>
  </div>
</x-admin-layout>
