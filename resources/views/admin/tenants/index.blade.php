<x-admin-layout title="ผู้เช่า">
  <div class="flex flex-col gap-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div class="flex gap-2">
        <input id="q" placeholder="ค้นหาชื่อ / อีเมล / บัตรประชาชน" class="w-full sm:w-96 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        <button id="btnSearch" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">ค้นหา</button>
      </div>
      <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-4 text-sm font-semibold text-white hover:bg-indigo-500 shadow-sm">+ เพิ่มผู้เช่า</a>
    </div>
  </div>

  <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left font-semibold">ชื่อ</th>
          <th class="px-4 py-3 text-left font-semibold">อีเมล</th>
          <th class="px-4 py-3 text-left font-semibold">เบอร์</th>
          <th class="px-4 py-3 text-left font-semibold">ห้องปัจจุบัน</th>
          <th class="px-4 py-3 text-right font-semibold">จัดการ</th>
        </tr>
      </thead>
      <tbody id="rows" class="divide-y divide-slate-100"></tbody>
    </table>
  </div>

  <div class="mt-6 flex items-center justify-between" id="pager"></div>

  <script>
    const qs = (k) => new URLSearchParams(location.search).get(k) || '';
    const setQs = (params) => {
      const u = new URL(location.href);
      Object.entries(params).forEach(([k,v]) => {
        if (v === '' || v === null || v === undefined) u.searchParams.delete(k);
        else u.searchParams.set(k, v);
      });
      history.replaceState({}, '', u.toString());
    };

    async function fetchTenants() {
      const q = document.getElementById('q').value.trim();
      const page = Number(qs('page') || 1);
      setQs({ q, page });

      const res = await window.api.get('/admin/tenants', { params: { q, per_page: 10, page }});
      const data = res.data;
      const tbody = document.getElementById('rows');

      if (!data.data?.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-slate-700">ไม่พบข้อมูลผู้เช่า</td></tr>`;
      } else {
        tbody.innerHTML = data.data.map(t => {
          const u = t.user || {};
          const room = t.current_room?.code || '-';
          return `
            <tr>
              <td class="px-4 py-3 font-semibold text-slate-900">${u.name || '-'}</td>
              <td class="px-4 py-3 text-slate-700">${u.email || '-'}</td>
              <td class="px-4 py-3 text-slate-700">${u.phone || '-'}</td>
              <td class="px-4 py-3 text-slate-700">${room}</td>
              <td class="px-4 py-3 text-right">
                <a href="/admin/tenants/${t.id}/edit" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">แก้ไข</a>
                <button data-del="${t.id}" class="ml-2 inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-black">ลบ</button>
              </td>
            </tr>
          `;
        }).join('');

        tbody.querySelectorAll('button[data-del]').forEach(btn => {
          btn.addEventListener('click', async () => {
            const id = btn.dataset.del;
            if (!confirm('ยืนยันลบผู้เช่านี้?')) return;
            try {
              await window.api.delete(`/admin/tenants/${id}`);
              await fetchTenants();
            } catch (e) {
              alert(e?.response?.data?.message || 'ลบไม่สำเร็จ');
            }
          });
        });
      }

      const pager = document.getElementById('pager');
      pager.innerHTML = `
        <div class="text-sm text-slate-600">หน้า ${data.current_page} / ${data.last_page}</div>
        <div class="flex gap-2">
          <button id="prev" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50" ${data.current_page<=1?'disabled style="opacity:.5"':''}>ก่อนหน้า</button>
          <button id="next" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50" ${data.current_page>=data.last_page?'disabled style="opacity:.5"':''}>ถัดไป</button>
        </div>
      `;
      pager.querySelector('#prev')?.addEventListener('click', () => {
        if (data.current_page<=1) return;
        setQs({ page: data.current_page-1 });
        fetchTenants();
      });
      pager.querySelector('#next')?.addEventListener('click', () => {
        if (data.current_page>=data.last_page) return;
        setQs({ page: data.current_page+1 });
        fetchTenants();
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('q').value = qs('q');
      document.getElementById('btnSearch').addEventListener('click', () => { setQs({ page: 1 }); fetchTenants(); });
      fetchTenants();
    });
  </script>
</x-admin-layout>
