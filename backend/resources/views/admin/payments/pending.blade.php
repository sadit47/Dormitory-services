<x-admin-layout title="ตรวจสอบการชำระเงิน">
  <div class="mt-2 text-sm text-slate-600">
    รายการสถานะรอตรวจสอบ (waiting)
  </div>

  <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 text-left font-semibold">เวลา</th>
          <th class="px-4 py-3 text-left font-semibold">ใบแจ้งหนี้</th>
          <th class="px-4 py-3 text-left font-semibold">ผู้เช่า</th>
          <th class="px-4 py-3 text-right font-semibold">ยอดชำระ</th>
          <th class="px-4 py-3 text-center font-semibold">สลิป</th>
          <th class="px-4 py-3 text-right font-semibold">จัดการ</th>
        </tr>
      </thead>
      <tbody id="rows" class="divide-y divide-slate-100"></tbody>
    </table>
  </div>

  <!-- Modal ดูรูป: คลิกพื้นหลังเพื่อปิด -->
  <div id="slipModal"
       class="fixed inset-0 z-[9999] hidden bg-black/70 p-4">
    <div class="flex h-full items-center justify-center">
      <!-- กันคลิกโดนรูปแล้วปิด -->
       <div class="card-strong overflow-hidden">
      <img id="slipImg"
           src=""
           class="mx-auto max-h-[45vh] max-w-[35vw] rounded-xl bg-white object-contain shadow-2xl">
    </div>
  </div>
  </div>

  <script>
    const fmtMoney = (n) =>
      new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        .format(Number(n || 0));

    const fmtDT = (v) => (v ? new Date(v).toLocaleString('th-TH') : '-');

    const slipModal = document.getElementById('slipModal');
    const slipImg   = document.getElementById('slipImg');

    function openSlip(url) {
      slipImg.src = url;
      slipModal.classList.remove('hidden');
    }

    function closeSlip() {
      slipModal.classList.add('hidden');
      slipImg.src = '';
    }

    // ✅ คลิกพื้นหลังปิด
    slipModal.addEventListener('click', closeSlip);

    // ✅ คลิกรูปไม่ปิด
    slipImg.addEventListener('click', (e) => e.stopPropagation());

    async function fetchPending() {
      const res = await window.api.get('/admin/payments/pending');
      const data = res.data;
      const tbody = document.getElementById('rows');

      if (!data.data?.length) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="px-4 py-6 text-center text-slate-600">
              ไม่มีรายการรอตรวจสอบ
            </td>
          </tr>`;
        return;
      }

      tbody.innerHTML = data.data.map(p => {
        const inv = p.invoice || {};
        const tenantName = inv.tenant?.user?.name || inv.tenant?.user?.email || '-';

        const slipHtml = p.slip
          ? `<img src="${p.slip.url}"
                  data-full="${p.slip.url}"
                  class="slip-thumb mx-auto h-16 w-16 cursor-pointer rounded-lg border object-cover hover:opacity-80">`
          : `<span class="text-slate-400">-</span>`;

        return `
          <tr>
            <td class="px-4 py-3">${fmtDT(p.created_at)}</td>
            <td class="px-4 py-3">${inv.invoice_no || '-'}</td>
            <td class="px-4 py-3">${tenantName}</td>
            <td class="px-4 py-3 text-right font-semibold">${fmtMoney(p.amount)}</td>
            <td class="px-4 py-3 text-center">${slipHtml}</td>
            <td class="px-4 py-3 text-right">
              <button data-appr="${p.id}"
                class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                อนุมัติ
              </button>
              <button data-rej="${p.id}"
                class="ml-2 rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500">
                ปฏิเสธ
              </button>
            </td>
          </tr>
        `;
      }).join('');

      // click thumbnail → modal
      document.querySelectorAll('.slip-thumb').forEach(img => {
        img.addEventListener('click', (e) => {
          e.stopPropagation();
          openSlip(img.dataset.full);
        });
      });

      // approve / reject
      tbody.querySelectorAll('[data-appr]').forEach(b =>
        b.addEventListener('click', async () => {
          if (!confirm('ยืนยันอนุมัติ?')) return;
          await window.api.post(`/admin/payments/${b.dataset.appr}/approve`);
          fetchPending();
        })
      );

      tbody.querySelectorAll('[data-rej]').forEach(b =>
        b.addEventListener('click', async () => {
          if (!confirm('ยืนยันปฏิเสธ?')) return;
          await window.api.post(`/admin/payments/${b.dataset.rej}/reject`);
          fetchPending();
        })
      );
    }

    document.addEventListener('DOMContentLoaded', fetchPending);

    document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const modal = document.getElementById('slipModal');
      if (!modal.classList.contains('hidden')) {
        modal.classList.add('hidden');
        document.getElementById('slipImg').src = '';
      }
    }
  });
  </script>
</x-admin-layout>
