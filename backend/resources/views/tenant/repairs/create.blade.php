<x-tenant-layout title="แจ้งซ่อม">
  <div class="card-strong p-5">
    <div class="text-xl font-extrabold text-slate-900">แจ้งซ่อม</div>
    <div class="mt-1 text-sm text-slate-600">กรอกข้อมูลปัญหาและแนบรูป (ถ้ามี)</div>

    <form id="repairForm" class="mt-6 space-y-4">
      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">หัวข้อ</label>
        <input id="title" type="text" class="input" placeholder="เช่น แอร์ไม่เย็น" required />
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">รายละเอียด</label>
        <textarea id="description" class="input h-28" placeholder="อธิบายอาการ..." required></textarea>
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">รูปภาพ (ได้หลายรูป)</label>
        <input id="images" type="file" class="input" accept="image/*" multiple />
        <div class="mt-2 text-xs text-slate-600">รองรับ .jpg .png ขนาดตามที่ระบบกำหนด</div>
      </div>

      <div class="flex items-center gap-2">
        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">ส่งแจ้งซ่อม</button>
        <a href="/tenant/repairs" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">ย้อนกลับ</a>
      </div>

      <div id="errBox" class="hidden rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"></div>
    </form>
  </div>

  <script>
    function showErr(msg) {
      const box = document.getElementById('errBox');
      box.textContent = msg || 'ทำรายการไม่สำเร็จ';
      box.classList.remove('hidden');
    }

    document.getElementById('repairForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      document.getElementById('errBox').classList.add('hidden');

      try {
        const fd = new FormData();
        fd.append('title', document.getElementById('title').value);
        fd.append('description', document.getElementById('description').value);

        const files = document.getElementById('images').files;
        for (const f of files) fd.append('images[]', f);

        await window.api.post('/tenant/repairs', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });

        window.location.href = '/tenant/repairs';
      } catch (e) {
        const msg = e?.response?.data?.message
          || (e?.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(' ') : null)
          || 'ส่งแจ้งซ่อมไม่สำเร็จ';
        showErr(msg);
      }
    });
  </script>
</x-tenant-layout>
