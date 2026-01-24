<x-admin-layout title="ใบแจ้งหนี้">
  <div class="flex flex-col gap-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
        <input id="q" placeholder="ค้นหาเลขใบแจ้งหนี้ / ชื่อ / อีเมล" class="w-full sm:w-80 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        <select id="type" class="w-full sm:w-auto min-w-[170px] rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
          <option value="">ทุกประเภท</option>
          <option value="rent">ค่าเช่า</option>
          <option value="utility">ค่าน้ำไฟ</option>
          <option value="repair">ค่าซ่อม</option>
          <option value="cleaning">ค่าทำความสะอาด</option>
        </select>
        <select id="status" class="w-full sm:w-auto min-w-[170px] rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
          <option value="">ทุกสถานะ</option>
          <option value="unpaid">ยังไม่ชำระ</option>
          <option value="paid">ชำระแล้ว</option>
          <option value="overdue">เกินกำหนด</option>
        </select>
        <button id="btnSearch" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">ค้นหา</button>
      </div>
      <a href="{{ route('admin.invoices.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-4 text-sm font-semibold text-white hover:bg-indigo-500 shadow-sm">+ สร้างใบแจ้งหนี้</a>
    </div>
  </div>

  <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left font-semibold">เลขใบแจ้งหนี้</th>
          <th class="px-4 py-3 text-left font-semibold">ผู้เช่า</th>
          <th class="px-4 py-3 text-left font-semibold">ห้อง</th>
          <th class="px-4 py-3 text-left font-semibold">ประเภท</th>
          <th class="px-4 py-3 text-right font-semibold">ยอดรวม</th>
          <th class="px-4 py-3 text-left font-semibold">สถานะ</th>
          <th class="px-4 py-3 text-right font-semibold">จัดการ</th>
        </tr>
      </thead>
      <tbody id="rows" class="divide-y divide-slate-100"></tbody>
    </table>
  </div>

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

    const typeLabel = (t) => ({ rent:'ค่าเช่า', utility:'ค่าน้ำไฟ', repair:'ค่าซ่อม', cleaning:'ค่าทำความสะอาด' }[t] || t || '-');
    const statusLabel = (s) => ({ unpaid:'ยังไม่ชำระ', paid:'ชำระแล้ว', overdue:'เกินกำหนด' }[s] || s || '-');

    async function fetchInvoices() {
      const q = document.getElementById('q').value.trim();
      const type = document.getElementById('type').value;
      const status = document.getElementById('status').value;
      const page = Number(qs('page') || 1);
      setQs({ q, type, status, page });

      const res = await window.api.get('/admin/invoices', { params: { q, type, status, per_page: 10, page }});
      const data = res.data;
      const tbody = document.getElementById('rows');

      if (!data.data?.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-6 text-slate-700">ไม่พบข้อมูลใบแจ้งหนี้</td></tr>`;
      } else {
        tbody.innerHTML = data.data.map(inv => {
          const tenantName = inv.tenant?.user?.name || inv.tenant?.user?.email || '-';
          const roomCode = inv.room?.code || '-';
          return `
            <tr>
              <td class="px-4 py-3 font-semibold text-slate-900">${inv.invoice_no}</td>
              <td class="px-4 py-3 text-slate-700">${tenantName}</td>
              <td class="px-4 py-3 text-slate-700">${roomCode}</td>
              <td class="px-4 py-3 text-slate-700">${typeLabel(inv.type)}</td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900">${fmtMoney(inv.total)}</td>
              <td class="px-4 py-3 text-slate-700">${statusLabel(inv.status)}</td>
              <td class="px-4 py-3 text-right">
                <a href="/admin/invoices/${inv.id}/edit" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">แก้ไข</a>
                <a href="/admin/invoices/${inv.id}/pdf" class="ml-2 inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">PDF</a>
                ${inv.status === 'paid' && inv.receipt_no ? `<a href="/admin/receipts/${inv.id}/pdf" class="ml-2 inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">ใบเสร็จ</a>` : ''}
                <button data-del="${inv.id}" class="ml-2 inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-black">ลบ</button>
              </td>
            </tr>
          `;
        }).join('');

        tbody.querySelectorAll('button[data-del]').forEach(btn => {
          btn.addEventListener('click', async () => {
            const id = btn.dataset.del;
            if (!confirm('ยืนยันลบใบแจ้งหนี้นี้?')) return;
            try {
              await window.api.delete(`/admin/invoices/${id}`);
              await fetchInvoices();
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
        fetchInvoices();
      });
      pager.querySelector('#next')?.addEventListener('click', () => {
        if (data.current_page>=data.last_page) return;
        setQs({ page: data.current_page+1 });
        fetchInvoices();
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('q').value = qs('q');
      document.getElementById('type').value = qs('type');
      document.getElementById('status').value = qs('status');
      document.getElementById('btnSearch').addEventListener('click', () => { setQs({ page: 1 }); fetchInvoices(); });
      fetchInvoices();
    });
  </script>
</x-admin-layout>
