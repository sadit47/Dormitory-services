<x-tenant-layout title="สร้างข้อมูลผู้เช่า">
  <div class="card-strong p-5">
    <div class="text-xl font-extrabold text-slate-900">สร้างข้อมูลผู้เช่า</div>
    <div class="mt-1 text-sm text-slate-600">กรอกข้อมูลเบื้องต้นสำหรับโปรไฟล์ผู้เช่า</div>

    <div id="errBox" class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <form id="profileCreateForm" class="mt-6 grid gap-4 sm:grid-cols-2">
      <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-semibold text-slate-700">ชื่อผู้ใช้งาน</label>
        <input id="name" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-300" required>
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">โทรศัพท์</label>
        <input id="phone" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-slate-300">
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

      <div class="sm:col-span-2 flex items-center gap-2">
        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">บันทึก</button>
        <a href="/tenant/dashboard" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">ยกเลิก</a>
      </div>
    </form>
  </div>

  <script>
    function showErr(msg) {
      const b = document.getElementById('errBox');
      b.textContent = msg;
      b.classList.remove('hidden');
    }

    document.getElementById('profileCreateForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const payload = {
          name: document.getElementById('name').value,
          phone: document.getElementById('phone').value || null,
          citizen_id: document.getElementById('citizen_id').value || null,
          address: document.getElementById('address').value || null,
          emergency_contact: document.getElementById('emergency_contact').value || null,
          start_date: document.getElementById('start_date').value || null,
          end_date: document.getElementById('end_date').value || null,
        };

        await window.api.post('/tenant/profile', payload);
        window.location.href = '/tenant/dashboard';
      } catch (err) {
        const msg = err?.response?.data?.message || 'บันทึกไม่สำเร็จ';
        showErr(msg);
      }
    });
  </script>
</x-tenant-layout>
