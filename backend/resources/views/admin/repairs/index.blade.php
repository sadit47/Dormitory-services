<x-admin-layout title="แจ้งซ่อม">
  <div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-center gap-2">
      <select id="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
        <option value="">ทุกสถานะ</option>
        <option value="submitted">รอดำเนินการ</option>
        <option value="in_progress">กำลังดำเนินการ</option>
        <option value="done">เสร็จสิ้น</option>
        <option value="rejected">ปฏิเสธ</option>
      </select>

      <input id="q" placeholder="ค้นหา (ชื่อผู้เช่า/อีเมล/เลขห้อง/หัวข้อ)"
             class="w-full max-w-md rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">

      <button id="btnSearch" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
        กรอง
      </button>
    </div>

    <div class="text-xs text-slate-500">
      คลิกรูปเพื่อขยาย, คลิกพื้นหลังเพื่อปิด, กด ESC เพื่อปิด
    </div>
  </div>

  <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left font-semibold">วันที่</th>
          <th class="px-4 py-3 text-left font-semibold">ผู้เช่า</th>
          <th class="px-4 py-3 text-left font-semibold">ห้อง</th>
          <th class="px-4 py-3 text-left font-semibold">หัวข้อ/รายละเอียด</th>
          <th class="px-4 py-3 text-center font-semibold">รูป</th>
          <th class="px-4 py-3 text-left font-semibold">สถานะ</th>
          <th class="px-4 py-3 text-right font-semibold">จัดการ</th>
        </tr>
      </thead>
      <tbody id="rows" class="divide-y divide-slate-100"></tbody>
    </table>
  </div>

  <div class="mt-6 flex items-center justify-between" id="pager"></div>

  <!-- Modal รูปแจ้งซ่อม (คลิกพื้นหลังปิด) -->
  <div id="imgModal" class="fixed inset-0 z-[9999] hidden bg-black/70 p-4">
    <div class="mx-auto flex h-full max-w-5xl items-center justify-center">
      <div class="card-strong overflow-hidden">
      <img id="imgModalTag"
           src=""
           class="mx-auto max-h-[45vh] max-w-[35vw] rounded-xl bg-white object-contain shadow-2xl">
    </div>
    </div>
  </div>

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

    const esc = (s='') => String(s)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;');

    const fmtDT = (v) => v ? new Date(v).toLocaleString('th-TH') : '-';

    function statusLabel(v) {
      if (v === 'submitted') return 'รอดำเนินการ';
      if (v === 'in_progress') return 'กำลังดำเนินการ';
      if (v === 'done') return 'เสร็จสิ้น';
      if (v === 'rejected') return 'ปฏิเสธ';
      return v || '-';
    }

    // ใช้ไฟล์แบบ public -> /storage/path
    function publicUrlFromFile(f) {
      // f.path เช่น repairs/xxx.jpg
      return `/storage/${f.path}`;
    }

    // modal open/close
    const imgModal = document.getElementById('imgModal');
    const imgModalTag = document.getElementById('imgModalTag');

    function openImg(url) {
      imgModalTag.src = url;
      imgModal.classList.remove('hidden');
      imgModal.classList.add('flex');
    }
    function closeImg() {
      imgModalTag.src = '';
      imgModal.classList.add('hidden');
      imgModal.classList.remove('flex');
    }

    // click background close
    imgModal.addEventListener('click', (e) => { if (e.target === imgModal) closeImg(); });

    // ESC close
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !imgModal.classList.contains('hidden')) closeImg();
    });

    async function fetchRepairs() {
      const status = document.getElementById('status').value;
      const q = document.getElementById('q').value.trim();
      const page = Number(qs('page') || 1);

      setQs({ status, q, page });

      const res = await window.api.get('/admin/repairs', {
        params: { status: status || undefined, q: q || undefined, per_page: 10, page }
      });

      const data = res.data;
      const tbody = document.getElementById('rows');

      if (!data.data?.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-10 text-center text-slate-600">ไม่พบรายการแจ้งซ่อม</td></tr>`;
      } else {
        tbody.innerHTML = data.data.map(r => {
          const d = fmtDT(r.requested_at || r.created_at);
          const tenant = r.tenant?.user?.name || r.tenant?.user?.email || '-';
          const room = r.room?.code || '-';

          const title = esc(r.title || '');
          const desc  = esc(r.description || '');

          const files = Array.isArray(r.files) ? r.files : [];
          const imgs = files.filter(f => (f.mime || '').startsWith('image/') && f.path);

          const thumbs = imgs.slice(0,3).map(f => {
            const url = `/files/${f.id}`; // ✅ แก้บั๊ก file -> f
            return `<img src="${url}" data-full="${url}"
                        onerror="this.style.opacity=.35; this.title='โหลดรูปไม่ได้: ${url}'"
                        class="repair-thumb mx-auto h-12 w-12 cursor-pointer rounded-lg border object-cover hover:opacity-80" />`;
          }).join('');

          const more = imgs.length > 3
            ? `<span class="text-xs text-slate-500">+${imgs.length-3}</span>`
            : '';

          return `
            <tr>
              <td class="px-4 py-3 text-slate-700">${d}</td>
              <td class="px-4 py-3 font-semibold text-slate-900">${esc(tenant)}</td>
              <td class="px-4 py-3 text-slate-700">${esc(room)}</td>

              <td class="px-4 py-3 text-slate-700">
                <div class="font-semibold text-slate-900">${title || '-'}</div>
                <div class="mt-1 line-clamp-2 text-sm text-slate-600">${desc || '-'}</div>
              </td>

              <td class="px-4 py-3 text-center">
                ${imgs.length
                  ? `<div class="flex items-center justify-center gap-2">${thumbs}${more}</div>`
                  : `<div class="text-slate-400">-</div>`
                }
              </td>

              <td class="px-4 py-3">
                <select data-status-id="${r.id}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                  <option value="submitted" ${r.status==='submitted'?'selected':''}>รอดำเนินการ</option>
                  <option value="in_progress" ${r.status==='in_progress'?'selected':''}>กำลังดำเนินการ</option>
                  <option value="done" ${r.status==='done'?'selected':''}>เสร็จสิ้น</option>
                  <option value="rejected" ${r.status==='rejected'?'selected':''}>ปฏิเสธ</option>
                </select>
                <div class="mt-1 text-xs text-slate-500">${statusLabel(r.status)}</div>
              </td>

              <td class="px-4 py-3 text-right">
                <button data-del="${r.id}" class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-black">ลบ</button>
              </td>
            </tr>
          `;
        }).join('');

        // click thumb -> modal
        document.querySelectorAll('.repair-thumb').forEach(img => {
          img.addEventListener('click', () => openImg(img.dataset.full));
        });

        // status change
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

        // delete
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

      // pager
      const pager = document.getElementById('pager');
      pager.innerHTML = `
        <div class="text-sm text-slate-600">หน้า ${data.current_page} / ${data.last_page}</div>
        <div class="flex gap-2">
          <button id="prev" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50"
            ${data.current_page<=1?'disabled style="opacity:.5"':''}>ก่อนหน้า</button>
          <button id="next" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50"
            ${data.current_page>=data.last_page?'disabled style="opacity:.5"':''}>ถัดไป</button>
        </div>
      `;
      pager.querySelector('#prev')?.addEventListener('click', () => {
        if (data.current_page>1) { setQs({ page: data.current_page-1 }); fetchRepairs(); }
      });
      pager.querySelector('#next')?.addEventListener('click', () => {
        if (data.current_page<data.last_page) { setQs({ page: data.current_page+1 }); fetchRepairs(); }
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('status').value = qs('status');
      document.getElementById('q').value = qs('q');

      document.getElementById('btnSearch').addEventListener('click', () => {
        setQs({ page: 1 });
        fetchRepairs();
      });

      fetchRepairs();
    });
  </script>
</x-admin-layout>
