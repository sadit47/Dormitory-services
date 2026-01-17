<x-guest-layout>
  <div class="max-w-md mx-auto text-center">
    <div class="text-2xl font-bold text-slate-900">เข้าสู่ระบบ</div>
    <div class="text-sm text-slate-500 mt-1">เลือกประเภทผู้ใช้งานก่อน</div>

    <div class="mt-6 grid grid-cols-1 gap-3">
      <a href="{{ route('login.admin') }}"
         class="w-full rounded-2xl bg-slate-900 text-white font-semibold py-3 hover:bg-slate-800 transition">
        เข้าใช้งาน (Admin)
      </a>

      <a href="{{ route('login.tenant') }}"
         class="w-full rounded-2xl bg-indigo-600 text-white font-semibold py-3 hover:bg-indigo-500 transition">
        เข้าใช้งาน (Tenant)
      </a>
    </div>
  </div>
</x-guest-layout>
