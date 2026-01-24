<x-admin-layout title="เพิ่มห้องพัก">
  <div class="max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form id="frm" class="space-y-4">
      <div>
        <label class="text-sm font-semibold">เลขห้อง</label>
        <input id="code" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="เช่น A101" required />
        <div class="mt-1 text-sm text-rose-600" id="err_code"></div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">ชั้น</label>
          <input id="floor" type="number" min="0" value="1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required />
          <div class="mt-1 text-sm text-rose-600" id="err_floor"></div>
        </div>

        <div>
          <label class="text-sm font-semibold">ประเภทห้อง</label>
          <select id="room_type_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"></select>
          <div class="mt-1 text-sm text-rose-600" id="err_room_type_id"></div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">ราคา/เดือน</label>
          <input id="price_monthly" type="number" step="0.01" min="0" value="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required />
          <div class="mt-1 text-sm text-rose-600" id="err_price_monthly"></div>
        </div>

        <div>
          <label class="text-sm font-semibold">สถานะ</label>
          <select id="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
            <option value="vacant">ว่าง</option>
            <option value="occupied">ไม่ว่าง</option>
            <option value="maintenance">ซ่อมบำรุง</option>
          </select>
          <div class="mt-1 text-sm text-rose-600" id="err_status"></div>
        </div>
      </div>

      <div class="flex gap-2">
        <a href="{{ route('admin.rooms.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">ย้อนกลับ</a>
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">บันทึก</button>
      </div>
    </form>
  </div>

  <script>
    const clearErr = () => ['code','floor','room_type_id','price_monthly','status'].forEach(k => (document.getElementById('err_'+k).textContent=''));
    const showErr = (errs) => {
      if (!errs) return;
      Object.entries(errs).forEach(([k,v]) => {
        const el = document.getElementById('err_'+k);
        if (el) el.textContent = Array.isArray(v) ? v[0] : String(v);
      });
    };

    async function loadMeta() {
      const res = await window.api.get('/admin/rooms/meta');
      const sel = document.getElementById('room_type_id');
      sel.innerHTML = '<option value="">-- เลือกประเภท --</option>';
      res.data.room_types.forEach(t => {
        const o = document.createElement('option');
        o.value = t.id; o.textContent = t.name;
        sel.appendChild(o);
      });
    }

    document.addEventListener('DOMContentLoaded', async () => {
      await loadMeta();
      document.getElementById('frm').addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErr();
        const payload = {
          code: document.getElementById('code').value.trim(),
          floor: Number(document.getElementById('floor').value),
          room_type_id: Number(document.getElementById('room_type_id').value),
          price_monthly: Number(document.getElementById('price_monthly').value),
          status: document.getElementById('status').value,
        };
        try {
          await window.api.post('/admin/rooms', payload);
          location.href = '{{ route('admin.rooms.index') }}';
        } catch (err) {
          const d = err?.response?.data;
          showErr(d?.errors);
          alert(d?.message || 'บันทึกไม่สำเร็จ');
        }
      });
    });
  </script>
</x-admin-layout>
