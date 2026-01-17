<x-tenant-layout>
  <x-slot name="title">ข้อมูลส่วนตัว</x-slot>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-2xl shadow-sm border p-6">
      <h2 class="text-lg font-semibold mb-4">ข้อมูลบัญชี</h2>

      <form method="POST" action="{{ auth()->user()->role === 'admin'
            ? route('admin.profile.update')
            : route('tenant.profile.update') }}">
        @csrf
        @method('PUT')

        <div>
          <label class="text-sm text-slate-600">ชื่อผู้ใช้</label>
          <input name="name" class="w-full mt-1 border rounded-xl px-3 py-2"
                 value="{{ old('name', $user->name) }}" required>
        </div>

        <div>
          <label class="text-sm text-slate-600">เบอร์โทร</label>
          <input name="phone" class="w-full mt-1 border rounded-xl px-3 py-2"
                 value="{{ old('phone', $user->phone) }}">
        </div>

        <hr class="my-2">

        <h3 class="font-semibold">ข้อมูลผู้เช่า</h3>

        <div>
          <label class="text-sm text-slate-600">เลขบัตรประชาชน</label>
          <input name="citizen_id" class="w-full mt-1 border rounded-xl px-3 py-2"
                 value="{{ old('citizen_id', $tenant->citizen_id) }}">
        </div>

        <div>
          <label class="text-sm text-slate-600">ที่อยู่</label>
          <textarea name="address" class="w-full mt-1 border rounded-xl px-3 py-2" rows="3">{{ old('address', $tenant->address) }}</textarea>
        </div>

        <div>
          <label class="text-sm text-slate-600">ผู้ติดต่อฉุกเฉิน</label>
          <input name="emergency_contact" class="w-full mt-1 border rounded-xl px-3 py-2"
                 value="{{ old('emergency_contact', $tenant->emergency_contact) }}">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm text-slate-600">วันเริ่มพัก</label>
            <input type="date" name="start_date" class="w-full mt-1 border rounded-xl px-3 py-2"
                   value="{{ old('start_date', optional($tenant->start_date)->format('Y-m-d')) }}">
          </div>
          <div>
            <label class="text-sm text-slate-600">วันสิ้นสุดพัก</label>
            <input type="date" name="end_date" class="w-full mt-1 border rounded-xl px-3 py-2"
                   value="{{ old('end_date', optional($tenant->end_date)->format('Y-m-d')) }}">
          </div>
        </div>

        <button class="px-4 py-2 rounded-xl bg-indigo-700 text-white font-semibold">
          บันทึก
        </button>
      </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border p-6">
      <h2 class="text-lg font-semibold mb-2">ห้องปัจจุบัน</h2>

      <div class="text-sm text-slate-600">
        ห้อง: <span class="font-semibold">{{ optional($tenant->currentRoom)->code ?? '-' }}</span>
      </div>

      <div class="mt-4 text-sm text-slate-500">
        * ถ้าห้องไม่ขึ้น แปลว่ายังไม่มี room_assignments status=active ของผู้เช่าคนนี้
      </div>
    </div>
  </div>
</x-tenant-layout>
