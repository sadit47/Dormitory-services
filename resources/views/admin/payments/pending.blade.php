<x-admin-layout title="ตรวจสอบการชำระเงิน">
  <div class="mt-2 text-sm text-slate-600">รายการสถานะรอตรวจสอบ (waiting)</div>

  <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left font-semibold">เวลา</th>
          <th class="px-4 py-3 text-left font-semibold">ใบแจ้งหนี้</th>
          <th class="px-4 py-3 text-left font-semibold">ผู้เช่า</th>
          <th class="px-4 py-3 text-right font-semibold">ยอดชำระ</th>
          <th class="px-4 py-3 text-right font-semibold">จัดการ</th>
        </tr>
      </thead>
      <tbody id="rows" class="divide-y divide-slate-100"></tbody>
    </table>
  </div>

  <script>
    const fmtMoney = (n) => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n||0));
    const fmtDT = (v) => v ? new Date(v).toLocaleString('th-TH') : '-';

    async function fetchPending() {
      const res = await window.api.get('/admin/payments/pending');
      const data = res.data;
      const tbody = document.getElementById('rows');
      if (!data.data?.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-slate-700">ไม่มีรายการรอตรวจสอบ</td></tr>`;
        return;
      }
      tbody.innerHTML = data.data.map(p => {
        const inv = p.invoice || {};
        const tenantName = inv.tenant?.user?.name || inv.tenant?.user?.email || '-';
        return `
          <tr>
            <td class="px-4 py-3 text-slate-700">${fmtDT(p.created_at)}</td>
            <td class="px-4 py-3 text-slate-700">${inv.invoice_no || '-'}</td>
            <td class="px-4 py-3 text-slate-700">${tenantName}</td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">${fmtMoney(p.amount)}</td>
            <td class="px-4 py-3 text-right">
              <button data-appr="${p.id}" class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500">อนุมัติ</button>
              <button data-rej="${p.id}" class="ml-2 rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500">ปฏิเสธ</button>
            </td>
          </tr>
        `;
      }).join('');

      tbody.querySelectorAll('button[data-appr]').forEach(b => b.addEventListener('click', async () => {
        const id = b.dataset.appr;
        if (!confirm('ยืนยันอนุมัติการชำระเงิน?')) return;
        try {
          await window.api.post(`/admin/payments/${id}/approve`);
          fetchPending();
        } catch (e) {
          alert(e?.response?.data?.message || 'ทำรายการไม่สำเร็จ');
        }
      }));

      tbody.querySelectorAll('button[data-rej]').forEach(b => b.addEventListener('click', async () => {
        const id = b.dataset.rej;
        if (!confirm('ยืนยันปฏิเสธการชำระเงิน?')) return;
        try {
          await window.api.post(`/admin/payments/${id}/reject`);
          fetchPending();
        } catch (e) {
          alert(e?.response?.data?.message || 'ทำรายการไม่สำเร็จ');
        }
      }));
    }

    document.addEventListener('DOMContentLoaded', fetchPending);
  </script>
</x-admin-layout>
