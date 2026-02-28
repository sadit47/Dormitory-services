<x-tenant-layout title="ข้อมูลส่วนตัว">
  <div class="card-strong p-5">
    <div class="text-xl font-extrabold text-slate-900">ข้อมูลส่วนตัว</div>
    <div class="mt-1 text-sm text-slate-600" id="room_hint">ห้อง -</div>

    <div id="errBox" class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <form id="profileForm" class="mt-6 grid gap-4 sm:grid-cols-2">
      <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-semibold text-slate-700">ชื่อผู้ใช้งาน</label>
        <input id="name" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-300" required>
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">โทรศัพท์</label>
        <input id="phone" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-300" placeholder="เช่น 08xxxxxxxx">
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">เลขบัตรประชาชน</label>
        <input id="citizen_id" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-300">
      </div>

      <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-semibold text-slate-700">ที่อยู่</label>
        <textarea id="address" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-300"></textarea>
      </div>

      <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-semibold text-slate-700">ผู้ติดต่อฉุกเฉิน</label>
        <input id="emergency_contact" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-300">
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">วันเริ่มสัญญา</label>
        <input id="start_date" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-300">
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">วันสิ้นสุดสัญญา</label>
        <input id="end_date" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-300">
      </div>

      <div class="sm:col-span-2 flex items-center gap-2 pt-2">
        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">บันทึก</button>
        <a href="/tenant/dashboard" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">กลับ</a>
      </div>
    </form>
  </div>

  <script>
    const errBox = document.getElementById('errBox');
    const showErr = (t) => { errBox.textContent = t; errBox.classList.remove('hidden'); };
    const hideErr = () => { errBox.classList.add('hidden'); errBox.textContent = ''; };
    const toDate = (s) => s ? String(s).slice(0, 10) : '';

    async function loadProfile() {
      const res = await window.api.get('/tenant/profile');
      const user = res.data.user || {};
      const tenant = res.data.tenant || {};
      const room = res.data.current_room;

      document.getElementById('name').value = user.name || '';
      document.getElementById('phone').value = user.phone || '';
      document.getElementById('citizen_id').value = tenant.citizen_id || '';
      document.getElementById('address').value = tenant.address || '';
      document.getElementById('emergency_contact').value = tenant.emergency_contact || '';
      document.getElementById('start_date').value = toDate(tenant.start_date);
      document.getElementById('end_date').value = toDate(tenant.end_date);

      if (room?.code) {
        document.getElementById('room_hint').textContent = `ห้อง ${room.code} • ${room.status || ''}`;
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadProfile().catch(e => showErr(e?.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ'));

      document.getElementById('profileForm').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        hideErr();

        try {
          await window.api.put('/tenant/profile', {
            name: document.getElementById('name').value,
            phone: document.getElementById('phone').value || null,
            citizen_id: document.getElementById('citizen_id').value || null,
            address: document.getElementById('address').value || null,
            emergency_contact: document.getElementById('emergency_contact').value || null,
            start_date: document.getElementById('start_date').value || null,
            end_date: document.getElementById('end_date').value || null,
          });

          window.location.href = '/tenant/dashboard';
        } catch (e) {
          const msg = e?.response?.data?.message
            || (e?.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' ') : null)
            || 'บันทึกไม่สำเร็จ';
          showErr(msg);
        }
      });
    });
  </script>
</x-tenant-layout>
