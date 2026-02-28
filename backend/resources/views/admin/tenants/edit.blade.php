<x-admin-layout title="แก้ไขผู้เช่า">
  <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form id="frm" class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">ชื่อ</label>
          <input id="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required />
          <div class="mt-1 text-sm text-rose-600" id="err_name"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">อีเมล</label>
          <input id="email" type="email" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required />
          <div class="mt-1 text-sm text-rose-600" id="err_email"></div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">เบอร์</label>
          <input id="phone" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          <div class="mt-1 text-sm text-rose-600" id="err_phone"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">รหัสผ่านใหม่ (ถ้าจะเปลี่ยน)</label>
          <input id="password" type="password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          <div class="mt-1 text-sm text-rose-600" id="err_password"></div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">เลขบัตรประชาชน</label>
          <input id="citizen_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          <div class="mt-1 text-sm text-rose-600" id="err_citizen_id"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">ห้อง (เลือกเพื่อย้าย/มอบหมาย)</label>
          <select id="room_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"></select>
          <div class="mt-1 text-sm text-rose-600" id="err_room_id"></div>
          <div class="mt-1 text-xs text-slate-500" id="cur_room"></div>
        </div>
      </div>

      <div>
        <label class="text-sm font-semibold">ที่อยู่</label>
        <textarea id="address" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
        <div class="mt-1 text-sm text-rose-600" id="err_address"></div>
      </div>

      <div>
        <label class="text-sm font-semibold">ติดต่อฉุกเฉิน</label>
        <input id="emergency_contact" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
        <div class="mt-1 text-sm text-rose-600" id="err_emergency_contact"></div>
      </div>

      <div class="flex gap-2">
        <a href="{{ route('admin.tenants.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">ย้อนกลับ</a>
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>

  <script>
    const TENANT_ID = @json($tenantId);
    const fields = ['name','email','password','phone','citizen_id','address','emergency_contact','room_id'];
    const clearErr = () => fields.forEach(k => {
      const el = document.getElementById('err_'+k);
      if (el) el.textContent='';
    });
    const showErr = (errs) => {
      if (!errs) return;
      Object.entries(errs).forEach(([k,v]) => {
        const el = document.getElementById('err_'+k);
        if (el) el.textContent = Array.isArray(v) ? v[0] : String(v);
      });
    };

    async function loadMeta() {
      const res = await window.api.get('/admin/tenants/meta');
      const sel = document.getElementById('room_id');
      sel.innerHTML = '<option value="">-- ไม่เปลี่ยนห้อง --</option>';
      res.data.vacant_rooms.forEach(r => {
        const o = document.createElement('option');
        o.value = r.id; o.textContent = r.code;
        sel.appendChild(o);
      });
    }

    async function loadTenant() {
      const res = await window.api.get(`/admin/tenants/${TENANT_ID}`);
      const t = res.data.data;
      const u = t.user || {};
      document.getElementById('name').value = u.name || '';
      document.getElementById('email').value = u.email || '';
      document.getElementById('phone').value = u.phone || '';
      document.getElementById('citizen_id').value = t.citizen_id || '';
      document.getElementById('address').value = t.address || '';
      document.getElementById('emergency_contact').value = t.emergency_contact || '';
      document.getElementById('cur_room').textContent = t.current_room?.code ? `ห้องปัจจุบัน: ${t.current_room.code}` : 'ยังไม่ผูกห้อง';
    }

    document.addEventListener('DOMContentLoaded', async () => {
      await loadMeta();
      await loadTenant();
      document.getElementById('frm').addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErr();
        const payload = {
          name: document.getElementById('name').value.trim(),
          email: document.getElementById('email').value.trim(),
          phone: document.getElementById('phone').value.trim(),
          citizen_id: document.getElementById('citizen_id').value.trim(),
          address: document.getElementById('address').value.trim(),
          emergency_contact: document.getElementById('emergency_contact').value.trim(),
          room_id: document.getElementById('room_id').value || null,
        };
        const pw = document.getElementById('password').value;
        if (pw) payload.password = pw;
        try {
          await window.api.put(`/admin/tenants/${TENANT_ID}`, payload);
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
