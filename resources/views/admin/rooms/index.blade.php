<x-admin-layout title="ห้องพัก">
  <div class="flex flex-col gap-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <input id="q" placeholder="ค้นหาเลขห้อง" class="w-full sm:w-72 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500" />

        <select id="type_id" class="w-full sm:w-auto min-w-[240px] rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-tight">
          <option value="">ทุกประเภท</option>
        </select>

        <select id="status" class="w-full sm:w-auto min-w-[160px] rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-tight">
          <option value="">ทุกสถานะ</option>
          <option value="vacant">ว่าง</option>
          <option value="occupied">ไม่ว่าง</option>
          <option value="maintenance">ซ่อมบำรุง</option>
        </select>

        <button id="btnSearch" class="w-full sm:w-auto rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">ค้นหา</button>
      </div>

      <a href="{{ route('admin.rooms.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-4 text-sm font-semibold text-white hover:bg-indigo-500 shadow-sm">+ เพิ่มห้อง</a>
    </div>

    <div class="flex flex-wrap gap-2" id="tabs"></div>
  </div>

  <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" id="roomsGrid"></div>

  <div class="mt-6 flex items-center justify-between" id="pager"></div>

  <script>
    const fmtMoney = (n) => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n||0));
    const qs = (k) => new URLSearchParams(location.search).get(k) || '';
    const setQs = (params) => {
      const u = new URL(location.href);
      Object.entries(params).forEach(([k,v]) => {
        if (v === '' || v === null || v === undefined) u.searchParams.delete(k);
        else u.searchParams.set(k, v);
      });
      history.replaceState({}, '', u.toString());
    };

    function statusUi(s) {
      if (s === 'vacant') return { text:'ว่าง', bar:'bg-emerald-600', pill:'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' };
      if (s === 'occupied') return { text:'ไม่ว่าง', bar:'bg-rose-600', pill:'bg-rose-50 text-rose-700 ring-1 ring-rose-200' };
      return { text:'ซ่อมบำรุง', bar:'bg-amber-500', pill:'bg-amber-50 text-amber-800 ring-1 ring-amber-200' };
    }

    async function loadMeta() {
      const meta = await window.api.get('/admin/rooms/meta');
      const typeSel = document.getElementById('type_id');
      meta.data.room_types.forEach(t => {
        const o = document.createElement('option');
        o.value = t.id; o.textContent = t.name;
        typeSel.appendChild(o);
      });
    }

    function renderTabs() {
      const tabs = [
        {k:'', label:'ทั้งหมด'},
        {k:'vacant', label:'ว่าง'},
        {k:'occupied', label:'ไม่ว่าง'},
        {k:'maintenance', label:'ซ่อมบำรุง'},
      ];
      const cur = document.getElementById('status').value;
      const el = document.getElementById('tabs');
      el.innerHTML = tabs.map(t => {
        const active = (cur === t.k);
        const cls = active ? 'bg-indigo-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-800';
        return `<button data-status="${t.k}" class="px-4 py-2 rounded-xl text-sm font-semibold ${cls}">${t.label}</button>`;
      }).join('');
      el.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', () => {
          document.getElementById('status').value = btn.dataset.status;
          setQs({ status: btn.dataset.status, page: 1 });
          renderTabs();
          fetchRooms();
        });
      });
    }

    async function fetchRooms() {
      const q = document.getElementById('q').value.trim();
      const status = document.getElementById('status').value;
      const typeId = document.getElementById('type_id').value;
      const page = Number(qs('page') || 1);

      setQs({ q, status, type_id: typeId, page });

      const res = await window.api.get('/admin/rooms', {
        params: {
          search: q,
          status: status || undefined,
          room_type_id: typeId || undefined, // (เผื่อคุณจะเพิ่ม filter ฝั่ง API)
          per_page: 12,
          page,
        }
      });

      const data = res.data;
      const grid = document.getElementById('roomsGrid');
      if (!data.data?.length) {
        grid.innerHTML = `<div class="col-span-full rounded-2xl border border-slate-200 bg-white p-6 text-slate-700">ไม่พบข้อมูลห้องพัก</div>`;
      } else {
        grid.innerHTML = data.data.map(r => {
          const ui = statusUi(r.status);
          const tenantName = r.active_assignment?.tenant?.user?.name || r.active_assignment?.tenant?.user?.email || '-';
          const tenantId = r.active_assignment?.tenant?.id;
          const startDate = r.active_assignment?.start_date ? new Date(r.active_assignment.start_date).toLocaleDateString('th-TH') : '';
          return `
            <div class="card-strong overflow-hidden">
              <div class="${ui.bar} h-2 rounded-t-2xl"></div>
              <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="text-lg font-extrabold tracking-tight text-slate-900">${r.code}</h3>
                      <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${ui.pill}">${ui.text}</span>
                    </div>
                    <div class="mt-2 text-sm text-slate-700">
                      ชั้น: <span class="font-semibold text-slate-900">${r.floor}</span>
                      <span class="mx-2 text-slate-300">•</span>
                      ประเภท: <span class="font-semibold text-slate-900">${r.room_type?.name || '-'}</span>
                    </div>
                    <div class="mt-1 text-sm text-slate-700">
                      ราคา/เดือน: <span class="font-semibold text-slate-900">${fmtMoney(r.price_monthly)}</span>
                      <span class="text-slate-500">บาท</span>
                    </div>
                  </div>
                  <div class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-800">ID #${r.id}</div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                  <div class="text-xs font-semibold text-slate-500">ผู้เช่าปัจจุบัน</div>
                  <div class="mt-1 text-sm font-bold text-slate-900">${r.status === 'occupied' ? tenantName : '-'}</div>
                  ${startDate ? `<div class="mt-1 text-xs text-slate-500">เข้าวันที่ ${startDate}</div>` : ''}
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                  <a href="${tenantId ? '/admin/tenants/' + tenantId + '/edit' : '#'}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50 ${tenantId ? '' : 'opacity-50 pointer-events-none'}">ดูผู้เช่า</a>
                  <a href="/admin/rooms/${r.id}/edit" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">แก้ไข</a>
                  <button data-del="${r.id}" class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-black">ลบ</button>
                </div>
              </div>
            </div>
          `;
        }).join('');

        grid.querySelectorAll('button[data-del]').forEach(btn => {
          btn.addEventListener('click', async () => {
            const id = btn.dataset.del;
            if (!confirm('ยืนยันลบห้องนี้?')) return;
            try {
              await window.api.delete(`/admin/rooms/${id}`);
              await fetchRooms();
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
        setQs({ page: data.current_page - 1 });
        fetchRooms();
      });
      pager.querySelector('#next')?.addEventListener('click', () => {
        if (data.current_page>=data.last_page) return;
        setQs({ page: data.current_page + 1 });
        fetchRooms();
      });
    }

    document.addEventListener('DOMContentLoaded', async () => {
      document.getElementById('q').value = qs('q');
      document.getElementById('status').value = qs('status');
      const typeId = qs('type_id');

      await loadMeta();
      if (typeId) document.getElementById('type_id').value = typeId;

      renderTabs();
      await fetchRooms();

      document.getElementById('btnSearch').addEventListener('click', () => {
        setQs({ page: 1 });
        fetchRooms();
      });
    });
  </script>
</x-admin-layout>
