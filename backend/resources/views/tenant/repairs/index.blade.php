<x-tenant-layout title="แจ้งซ่อม">
  <div class="flex items-center justify-between">
    <div>
      <div class="text-xl font-extrabold text-slate-900">ประวัติแจ้งซ่อม</div>
      <div class="text-sm text-slate-600">ดูสถานะการดำเนินการ</div>
    </div>
    <a href="/tenant/repairs/create" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">แจ้งซ่อม</a>
  </div>

  <div class="mt-6 card-strong overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">วันแจ้ง</th>
            <th class="px-4 py-3 text-left font-semibold">หัวข้อ</th>
            <th class="px-4 py-3 text-left font-semibold">สถานะ</th>
          </tr>
        </thead>
        <tbody id="repBody" class="divide-y divide-slate-200"></tbody>
      </table>
    </div>
    <div id="repEmpty" class="hidden p-4 text-sm text-slate-600">ไม่มีรายการ</div>

    <div class="border-t border-slate-200 bg-white px-4 py-3">
      <div class="flex flex-wrap items-center justify-between gap-2" id="repPager"></div>
    </div>
  </div>

  <script>
    const dt = (s) => s ? new Date(s).toLocaleString('th-TH') : '-';
    const badge = (s) => {
      const map = {
        submitted: ['ส่งแล้ว', 'bg-slate-100 text-slate-700'],
        pending: ['รอดำเนินการ', 'bg-amber-100 text-amber-800'],
        in_progress: ['กำลังดำเนินการ', 'bg-blue-100 text-blue-800'],
        done: ['เสร็จสิ้น', 'bg-emerald-100 text-emerald-800'],
        cancelled: ['ยกเลิก', 'bg-rose-100 text-rose-800'],
      };
      const [t, cls] = map[s] || [s || '-', 'bg-slate-100 text-slate-700'];
      return `<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${cls}">${t}</span>`;
    };

    function renderPager(p) {
      const wrap = document.getElementById('repPager');
      if (!p?.links?.length) { wrap.innerHTML = ''; return; }

      const btn = (label, url, active, page) => {
        const disabled = !url;
        return `
          <button
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold ${active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white hover:bg-slate-50'} ${disabled ? 'opacity-50 cursor-not-allowed' : ''}"
            data-page="${page ?? ''}"
            ${disabled ? 'disabled' : ''}
          >${label}</button>
        `;
      };

      const items = p.links.map(l => btn(l.label.replace('&laquo;','«').replace('&raquo;','»'), l.url, l.active, l.page)).join('');
      wrap.innerHTML = `<div class="flex flex-wrap items-center gap-2">${items}</div><div class="text-xs text-slate-600">แสดง ${p.from ?? 0}-${p.to ?? 0} / ${p.total ?? 0}</div>`;

      wrap.querySelectorAll('button[data-page]').forEach(b => {
        if (b.disabled) return;
        b.addEventListener('click', () => load(Number(b.dataset.page || 1)));
      });
    }

    async function load(page = 1) {
      const res = await window.api.get('/tenant/repairs', { params: { page } });
      const p = res.data;

      const body = document.getElementById('repBody');
      if (!p.data?.length) {
        document.getElementById('repEmpty').classList.remove('hidden');
        body.innerHTML = '';
        renderPager(p);
        return;
      }

      document.getElementById('repEmpty').classList.add('hidden');
      body.innerHTML = p.data.map(r => `
        <tr>
          <td class="px-4 py-3 text-slate-700">${dt(r.created_at)}</td>
          <td class="px-4 py-3">
            <div class="font-semibold text-slate-900">${r.title || '-'}</div>
            <div class="mt-1 text-xs text-slate-600 line-clamp-2">${(r.description || '').replace(/</g,'&lt;')}</div>
          </td>
          <td class="px-4 py-3">${badge(r.status)}</td>
        </tr>
      `).join('');

      renderPager(p);
    }

    document.addEventListener('DOMContentLoaded', () => {
      load().catch(e => alert(e?.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ'));
    });
  </script>
</x-tenant-layout>
