<x-admin-layout title="แจ้งซ่อม">
  <div class="flex flex-col gap-4">
    <div class="flex gap-2">
      <select id="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
        <option value="">ทุกสถานะ</option>
        <option value="pending">รอดำเนินการ</option>
        <option value="in_progress">กำลังดำเนินการ</option>
        <option value="done">เสร็จสิ้น</option>
      </select>
      <button id="btnSearch" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">กรอง</button>
    </div>
  </div>

  <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left font-semibold">วันที่</th>
          <th class="px-4 py-3 text-left font-semibold">ผู้เช่า</th>
          <th class="px-4 py-3 text-left font-semibold">ห้อง</th>
          <th class="px-4 py-3 text-left font-semibold">รายละเอียด</th>
          <th class="px-4 py-3 text-left font-semibold">สถานะ</th>
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

    async function fetchRepairs() {
      const status = document.getElementById('status').value;
      const page = Number(qs('page') || 1);
      setQs({ status, page });

      const res = await window.api.get('/admin/repairs', { params: { status: status || undefined, per_page: 10, page }});
      const data = res.data;
      const tbody = document.getElementById('rows');

      if (!data.data?.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-6 text-slate-700">ไม่พบรายการแจ้งซ่อม</td></tr>`;
      } else {
        tbody.innerHTML = data.data.map(r => {
          const d = r.requested_at ? new Date(r.requested_at).toLocaleString('th-TH') : '-';
          const tenant = r.tenant?.user?.name || r.tenant?.user?.email || '-';
          const room = r.room?.code || '-';
          return `
            <tr>
              <td class="px-4 py-3 text-slate-700">${d}</td>
              <td class="px-4 py-3 font-semibold text-slate-900">${tenant}</td>
              <td class="px-4 py-3 text-slate-700">${room}</td>
              <td class="px-4 py-3 text-slate-700">${(r.description||'').replaceAll('<','&lt;')}</td>
              <td class="px-4 py-3">
                <select data-status-id="${r.id}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                  <option value="pending" ${r.status==='pending'?'selected':''}>รอดำเนินการ</option>
                  <option value="in_progress" ${r.status==='in_progress'?'selected':''}>กำลังดำเนินการ</option>
                  <option value="done" ${r.status==='done'?'selected':''}>เสร็จสิ้น</option>
                </select>
              </td>
              <td class="px-4 py-3 text-right">
                <button data-del="${r.id}" class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-black">ลบ</button>
              </td>
            </tr>
          `;
        }).join('');

        tbody.querySelectorAll('select[data-status-id]').forEach(sel => {
          sel.addEventListener('change', async () => {
            const id = sel.dataset.statusId;
            try {
              await window.api.patch(`/admin/repairs/${id}/status`, { status: sel.value });
            } catch (e) {
              alert(e?.response?.data?.message || 'อัปเดตไม่สำเร็จ');
            }
          });
        });

        tbody.querySelectorAll('button[data-del]').forEach(btn => {
          btn.addEventListener('click', async () => {
            const id = btn.dataset.del;
            if (!confirm('ยืนยันลบรายการนี้?')) return;
            try {
              await window.api.delete(`/admin/repairs/${id}`);
              await fetchRepairs();
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
      pager.querySelector('#prev')?.addEventListener('click', () => { if (data.current_page>1) { setQs({ page: data.current_page-1 }); fetchRepairs(); }});
      pager.querySelector('#next')?.addEventListener('click', () => { if (data.current_page<data.last_page) { setQs({ page: data.current_page+1 }); fetchRepairs(); }});
    }

    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('status').value = qs('status');
      document.getElementById('btnSearch').addEventListener('click', () => { setQs({ page: 1 }); fetchRepairs(); });
      fetchRepairs();
    });
  </script>
</x-admin-layout>
