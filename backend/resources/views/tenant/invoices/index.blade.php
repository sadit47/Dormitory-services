<x-tenant-layout title="ใบแจ้งหนี้">
  <div class="card-strong overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="font-semibold text-slate-800">รายการใบแจ้งหนี้</div>
      <div class="flex items-center gap-2">
        <select id="per_page" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
          <option value="10">10 ต่อหน้า</option>
          <option value="20">20 ต่อหน้า</option>
          <option value="50">50 ต่อหน้า</option>
        </select>
        <button id="btnRefresh" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-100">รีเฟรช</button>
      </div>
    </div>

    <div class="p-4">
      <div id="empty" class="hidden text-sm text-slate-600">ไม่มีรายการ</div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="text-slate-600">
            <tr class="border-b">
              <th class="p-2 text-left">เลขใบแจ้งหนี้</th>
              <th class="p-2 text-left">ประเภท</th>
              <th class="p-2 text-left">งวด</th>
              <th class="p-2 text-right">ยอด</th>
              <th class="p-2 text-left">ครบกำหนด</th>
              <th class="p-2 text-left">สถานะ</th>
              <th class="p-2 text-right">จัดการ</th>
            </tr>
          </thead>
          <tbody id="tbody" class="text-slate-900"></tbody>
        </table>
      </div>

      <div class="mt-4 flex flex-wrap items-center gap-2" id="pager"></div>
    </div>
  </div>

  <script>
    const fmtMoney = (n) => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n || 0));
    const dt = (s) => s ? new Date(s).toLocaleDateString('th-TH') : '-';

    const badge = (status) => {
      const map = {
        paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        unpaid: 'bg-rose-50 text-rose-700 border-rose-200',
        partial: 'bg-amber-50 text-amber-700 border-amber-200',
        pending: 'bg-slate-50 text-slate-700 border-slate-200',
      };
      const cls = map[status] || 'bg-slate-50 text-slate-700 border-slate-200';
      return `<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold ${cls}">${status || '-'}</span>`;
    };

    function pageFromUrl(url) {
      if (!url) return null;
      try {
        const u = new URL(url);
        const p = u.searchParams.get('page');
        return p ? Number(p) : null;
      } catch (e) {
        return null;
      }
    }

    async function load(page = 1) {
      const perPage = Number(document.getElementById('per_page').value || 10);
      const res = await window.api.get('/tenant/invoices', { params: { page, per_page: perPage } });
      const d = res.data;

      const rows = (d.data || []).map(inv => {
        const period = (inv.period_month && inv.period_year) ? `${inv.period_month}/${inv.period_year}` : '-';
        const pdfUrl = `/tenant/invoices/${inv.id}/pdf`;
        const receiptUrl = inv.receipt_no ? `/tenant/invoices/${inv.id}/receipt-pdf` : null;
        const canPay = ['unpaid', 'partial'].includes(inv.status);
        const payUrl = canPay ? `/tenant/payments/create/${inv.id}` : null;

        return `
          <tr class="border-b">
            <td class="p-2 font-semibold">${inv.invoice_no || '-'}</td>
            <td class="p-2">${inv.type || '-'}</td>
            <td class="p-2">${period}</td>
            <td class="p-2 text-right">${fmtMoney(inv.total)}</td>
            <td class="p-2">${dt(inv.due_date)}</td>
            <td class="p-2">${badge(inv.status)}</td>
            <td class="p-2 text-right">
              <div class="inline-flex flex-wrap justify-end gap-2">
                <a href="${pdfUrl}" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">PDF</a>
                ${receiptUrl ? `<a href="${receiptUrl}" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">ใบเสร็จ</a>` : ''}
                ${payUrl ? `<a href="${payUrl}" class="rounded-xl border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">ชำระเงิน</a>` : ''}
              </div>
            </td>
          </tr>
        `;
      }).join('');

      document.getElementById('tbody').innerHTML = rows;
      document.getElementById('empty').classList.toggle('hidden', (d.data || []).length > 0);

      // pager from links
      const pager = document.getElementById('pager');
      const links = d.links || [];
      pager.innerHTML = links.map(l => {
        const p = pageFromUrl(l.url);
        const disabled = !l.url;
        const active = !!l.active;
        const cls = active
          ? 'bg-slate-900 text-white border-slate-900'
          : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50';
        return `
          <button
            class="rounded-xl border px-3 py-1.5 text-xs font-semibold ${cls} ${disabled ? 'opacity-40 cursor-not-allowed' : ''}"
            ${disabled ? 'disabled' : ''}
            data-page="${p || ''}">
            ${l.label}
          </button>
        `;
      }).join('');

      pager.querySelectorAll('button[data-page]').forEach(btn => {
        btn.addEventListener('click', () => {
          const p = Number(btn.getAttribute('data-page'));
          if (p) load(p).catch(err => alert(err?.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ'));
        });
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('btnRefresh').addEventListener('click', () => load(1).catch(err => alert(err?.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ')));
      document.getElementById('per_page').addEventListener('change', () => load(1).catch(err => alert(err?.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ')));
      load(1).catch(err => alert(err?.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ'));
    });
  </script>
</x-tenant-layout>
