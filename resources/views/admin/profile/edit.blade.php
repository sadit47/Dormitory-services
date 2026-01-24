<x-admin-layout title="โปรไฟล์">
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

      <div id="success" class="hidden mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
        บันทึกสำเร็จ
      </div>

      <form id="frm" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold mb-1">ชื่อผู้ใช้</label>
            <input id="name" class="w-full rounded-2xl border border-slate-500 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
            <div class="mt-1 text-sm text-rose-600" id="err_name"></div>
          </div>
          <div>
            <label class="block text-sm font-bold mb-1">เบอร์โทร</label>
            <input id="phone" class="w-full rounded-2xl border border-slate-500 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="เช่น 0123456789">
            <div class="mt-1 text-sm text-rose-600" id="err_phone"></div>
          </div>
        </div>

        <div class="flex items-center gap-2 pt-2">
          <button class="px-5 py-3 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-500">บันทึก</button>
          <a href="{{ route('admin.dashboard') }}" class="px-5 py-3 rounded-2xl border bg-slate-900 text-white hover:bg-slate-800 font-bold">ย้อนกลับ</a>
        </div>
      </form>
    </div>

    <div class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
      <div class="font-extrabold text-lg mb-3">ข้อมูลบัญชี</div>
      <div class="space-y-3 text-sm">
        <div>
          <div class="text-slate-500">อีเมล</div>
          <div class="font-bold break-all" id="email">-</div>
        </div>
        <div>
          <div class="text-slate-500">Role</div>
          <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-bold" id="role">-</div>
        </div>
        <div class="pt-2 text-xs text-slate-400">* หน้าโปรไฟล์นี้แก้เฉพาะชื่อ/เบอร์</div>
      </div>
    </div>
  </div>

  <script>
    const clearErr = () => ['name','phone'].forEach(k => (document.getElementById('err_'+k).textContent=''));
    const showErr = (errs) => {
      if (!errs) return;
      Object.entries(errs).forEach(([k,v]) => {
        const el = document.getElementById('err_'+k);
        if (el) el.textContent = Array.isArray(v) ? v[0] : String(v);
      });
    };

    async function loadProfile() {
      const res = await window.api.get('/admin/profile');
      const u = res.data.data;
      document.getElementById('name').value = u.name || '';
      document.getElementById('phone').value = u.phone || '';
      document.getElementById('email').textContent = u.email || '-';
      document.getElementById('role').textContent = '{{ auth()->user()->role }}';
    }

    document.addEventListener('DOMContentLoaded', async () => {
      await loadProfile();
      document.getElementById('frm').addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErr();
        document.getElementById('success').classList.add('hidden');
        try {
          await window.api.put('/admin/profile', {
            name: document.getElementById('name').value.trim(),
            phone: document.getElementById('phone').value.trim() || null,
          });
          document.getElementById('success').classList.remove('hidden');
        } catch (err) {
          const d = err?.response?.data;
          showErr(d?.errors);
          alert(d?.message || 'บันทึกไม่สำเร็จ');
        }
      });
    });
  </script>
</x-admin-layout>
