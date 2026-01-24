<x-admin-layout title="เพิ่มผู้เช่า">
  <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form id="frm" class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">ชื่อ</label>
          <input id="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required />
          <div class="mt-1 text-sm text-rose-600" id="err_name"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">อีเมล (ใช้ล็อกอิน)</label>
          <input id="email" type="email" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required />
          <div class="mt-1 text-sm text-rose-600" id="err_email"></div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">รหัสผ่าน</label>
          <input id="password" type="password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required />
          <div class="mt-1 text-sm text-rose-600" id="err_password"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">เบอร์โทร</label>
          <input id="phone" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          <div class="mt-1 text-sm text-rose-600" id="err_phone"></div>
        </div>
      </div>

      <div>
        <label class="text-sm font-semibold">เลขบัตรประชาชน</label>
        <input id="citizen_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
        <div class="mt-1 text-sm text-rose-600" id="err_citizen_id"></div>
      </div>

      <div>
        <label class="text-sm font-semibold">ที่อยู่</label>
        <textarea id="address" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
        <div class="mt-1 text-sm text-rose-600" id="err_address"></div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">ห้อง (ถ้ามอบหมายทันที)</label>
          <select id="room_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"></select>
          <div class="mt-1 text-sm text-rose-600" id="err_room_id"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">ผู้ติดต่อฉุกเฉิน</label>
          <input id="emergency_contact" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          <div class="mt-1 text-sm text-rose-600" id="err_emergency_contact"></div>
        </div>
      </div>

      <div class="flex gap-2">
        <a href="{{ route('admin.tenants.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">ย้อนกลับ</a>
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">บันทึก</button>
      </div>
    </form>
  </div>

  <script>
    const fields = ['name','email','password','phone','citizen_id','address','room_id','emergency_contact'];
    const clearErr = () => fields.forEach(k => { const el=document.getElementById('err_'+k); if(el) el.textContent=''; });
    const showErr = (errs) => {
      if (!errs) return;
      Object.entries(errs).forEach(([k,v]) => {
        const el=document.getElementById('err_'+k);
        if (el) el.textContent = Array.isArray(v)?v[0]:String(v);
      });
    };

    async function loadMeta() {
      const res = await window.api.get('/admin/tenants/meta');
      const sel = document.getElementById('room_id');
      sel.innerHTML = '<option value="">-- ไม่ระบุ --</option>';
      res.data.vacant_rooms.forEach(r => {
        const o = document.createElement('option');
        o.value = r.id; o.textContent = r.code;
        sel.appendChild(o);
      });
    }

    document.addEventListener('DOMContentLoaded', async () => {
      await loadMeta();
      document.getElementById('frm').addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErr();
        const payload = {
          name: document.getElementById('name').value.trim(),
          email: document.getElementById('email').value.trim(),
          password: document.getElementById('password').value,
          phone: document.getElementById('phone').value.trim() || null,
          citizen_id: document.getElementById('citizen_id').value.trim() || null,
          address: document.getElementById('address').value.trim() || null,
          emergency_contact: document.getElementById('emergency_contact').value.trim() || null,
          room_id: document.getElementById('room_id').value ? Number(document.getElementById('room_id').value) : null,
        };
        try {
          await window.api.post('/admin/tenants', payload);
          location.href = '{{ route('admin.tenants.index') }}';
        } catch (err) {
          const d = err?.response?.data;
          showErr(d?.errors);
          alert(d?.message || 'บันทึกไม่สำเร็จ');
        }
      });
    });
  </script>
</x-admin-layout>
