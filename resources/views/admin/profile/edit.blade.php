<x-admin-layout title="โปรไฟล์">
  <div class="card-strong overflow-hidden">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
      <div class="flex items-start justify-between gap-3 mb-5">
        <div>
          <div class="text-sm text-slate-500">ตั้งค่าบัญชี</div>
          <h2 class="text-xl font-extrabold">แก้ไขข้อมูลตัวเอง</h2>
          <p class="text-sm text-slate-500 mt-1">อัปเดตชื่อและเบอร์โทรของบัญชีแอดมิน</p>
        </div>
        <div class="h-12 w-12 rounded-3xl bg-indigo-50 grid place-items-center text-2xl">👤</div>
      </div>

      @if ($errors->any())
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          <div class="font-bold mb-1">บันทึกไม่สำเร็จ</div>
          <ul class="list-disc pl-5 space-y-1">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold mb-1">ชื่อผู้ใช้</label>
            <input name="name" value="{{ old('name', auth()->user()->name) }}"
                   class="w-full rounded-2xl border border-slate-500 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200"
                   required>
                   
          </div>
          <div>
            <label class="block text-sm font-bold mb-1">เบอร์โทร</label>
            <input name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                   class="w-full rounded-2xl border border-slate-500 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200"
                   placeholder="เช่น 0123456789">
          </div>
        </div>

        <div class="flex items-center gap-2 pt-2">
          <button class="px-5 py-3 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-500">
            บันทึก
          </button>
          <a href="{{ route('admin.dashboard') }}"
             class="px-5 py-3 rounded-2xl border bg-slate-900 text-white hover:bg-slate-800 font-bold">
            ย้อนกลับ
          </a>
    
        </div>
      </form>
    </div>

    <div class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
      <div class="font-extrabold text-lg mb-3">ข้อมูลบัญชี</div>
      <div class="space-y-3 text-sm">
        <div>
          <div class="text-slate-500">อีเมล</div>
          <div class="font-bold break-all">{{ auth()->user()->email }}</div>
        </div>

        <div>
          <div class="text-slate-500">Role</div>
          <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-bold">
            {{ auth()->user()->role }}
          </div>
        </div>

        <div class="pt-2 text-xs text-slate-400">
          * หน้าโปรไฟล์นี้แก้เฉพาะชื่อ/เบอร์
        </div>
      </div>
    </div>
  </div>
</div>
</x-admin-layout>
