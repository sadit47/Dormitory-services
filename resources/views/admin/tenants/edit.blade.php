<x-admin-layout title="แก้ไขผู้เช่า">
  <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-sm text-slate-500">Tenant ID #{{ $tenant->id }}</div>
        <div class="mt-1 text-lg font-bold text-slate-900">
          {{ $tenant->user?->name ?? $tenant->user?->email ?? '-' }}
        </div>
        <div class="text-sm text-slate-600">
          {{ $tenant->user?->email ?? '' }}
        </div>
      </div>

      <a href="{{ route('admin.dashboard') }}"
             class="px-5 py-3 rounded-2xl border bg-slate-900 text-white hover:bg-slate-800 font-bold">
        ย้อนกลับ
      </a>
    </div>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('admin.tenants.update', $tenant) }}">
      @csrf
      @method('PUT')

      <div class="card-strong p-5 mb-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">เลขบัตรประชาชน</label>
          <input name="citizen_id" value="{{ old('citizen_id', $tenant->citizen_id) }}"
                 class="mt-1 w-full rounded-xl border border-slate-500 px-3 py-2 text-sm" />
          @error('citizen_id') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-semibold">เบอร์ติดต่อฉุกเฉิน</label>
          <input name="emergency_contact" value="{{ old('emergency_contact', $tenant->emergency_contact) }}"
                 class="mt-1 w-full rounded-xl border border-slate-500 px-3 py-2 text-sm" />
          @error('emergency_contact') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>
      </div>

      <div>
        <label class="text-sm font-semibold">ที่อยู่</label>
        <textarea name="address" rows="3"
                  class="mt-1 w-full rounded-xl border border-slate-500 px-3 py-2 text-sm">{{ old('address', $tenant->address) }}</textarea>
        @error('address') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">วันเริ่มพัก</label>
          <input type="date" name="start_date" value="{{ old('start_date', optional($tenant->start_date)->format('Y-m-d')) }}"
                 class="mt-1 w-full rounded-xl border border-slate-500 px-3 py-2 text-sm" />
          @error('start_date') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-semibold">วันย้ายออก</label>
          <input type="date" name="end_date" value="{{ old('end_date', optional($tenant->end_date)->format('Y-m-d')) }}"
                 class="mt-1 w-full rounded-xl border border-slate-500 px-3 py-2 text-sm" />
          @error('end_date') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="flex gap-2 pt-2">
        <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
          บันทึก
        </button>

        <a href="{{ route('admin.dashboard') }}"
             class="px-5 py-3 rounded-2xl border bg-slate-900 text-white hover:bg-slate-800 font-bold">
          ไปหน้าห้องพัก
        </a>
      </div>
    </form>
    </div>
  </div>
  </div>
</x-admin-layout>
