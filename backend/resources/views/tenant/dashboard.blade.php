<x-tenant-layout title="แดชบอร์ด">
  <div class="flex flex-col gap-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-sm text-slate-600">สวัสดี</div>
          <div class="mt-1 text-2xl font-extrabold text-slate-900" id="me_name">-</div>
          <div class="mt-1 text-sm text-slate-600" id="room_line">ห้อง -</div>
        </div>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="card-strong p-4">
        <div class="text-xs font-semibold text-slate-500">ยอดค้างชำระ</div>
        <div class="mt-2 text-2xl font-extrabold text-slate-900" id="k_unpaid_total">-</div>
        <div class="mt-1 text-xs text-slate-600">รวมใบแจ้งหนี้ยังไม่ชำระ</div>
      </div>
      <div class="card-strong p-4">
        <div class="text-xs font-semibold text-slate-500">จำนวนใบแจ้งหนี้ค้าง</div>
        <div class="mt-2 text-2xl font-extrabold text-slate-900" id="k_unpaid_count">-</div>
        <div class="mt-1 text-xs text-slate-600">สถานะ unpaid/partial</div>
      </div>
      <div class="card-strong p-4">
        <div class="text-xs font-semibold text-slate-500">แจ้งซ่อมกำลังดำเนินการ</div>
        <div class="mt-2 text-2xl font-extrabold text-slate-900" id="k_repair_open">-</div>
        <div class="mt-1 text-xs text-slate-600">submitted/pending/in_progress</div>
      </div>
      <div class="card-strong p-4">
        <div class="text-xs font-semibold text-slate-500">ทำความสะอาดกำลังดำเนินการ</div>
        <div class="mt-2 text-2xl font-extrabold text-slate-900" id="k_clean_open">-</div>
        <div class="mt-1 text-xs text-slate-600">submitted/pending/in_progress</div>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 text-slate-700 font-semibold">ใบแจ้งหนี้ล่าสุด</div>
        <div class="p-4">
          <div id="invEmpty" class="hidden text-sm text-slate-600">ไม่มีรายการ</div>
          <div class="space-y-3" id="invList"></div>
          <a href="/tenant/invoices" class="mt-4 inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">ไปหน้าใบแจ้งหนี้</a>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 text-slate-700 font-semibold">ค้างชำระ (แนะนำให้ชำระ)</div>
        <div class="p-4">
          <div id="unpaidEmpty" class="hidden text-sm text-slate-600">ไม่มีรายการ</div>
          <div class="space-y-3" id="unpaidList"></div>
        </div>
      </div>
    </div>
  </div>

  <script>
  const fmtMoney = (n) => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n || 0));
  const dt = (s) => s ? new Date(s).toLocaleDateString('th-TH') : '-';

  function statusBadge(status) {
    const map = {
      paid: ['ชำระแล้ว','bg-emerald-100 text-emerald-800'],
      unpaid: ['ยังไม่ชำระ','bg-amber-100 text-amber-800'],
      partial: ['ชำระบางส่วน','bg-amber-100 text-amber-800'],
      pending: ['รอตรวจสอบ','bg-sky-100 text-sky-800'],
      rejected: ['ถูกปฏิเสธ','bg-rose-100 text-rose-800'],
    };
    const [label, cls] = map[status] || [status || '-', 'bg-slate-100 text-slate-700'];
    return `<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${cls}">${label}</span>`;
  }

  function invCard(inv) {
    const period = (inv.period_month && inv.period_year) ? `${inv.period_month}/${inv.period_year}` : '-';
    const payUrl = `/tenant/payments/create/${inv.id}`;
    const pdfUrl = `/tenant/invoices/${inv.id}/pdf`;
    const receiptUrl = `/tenant/invoices/${inv.id}/receipt-pdf`;

    const right = (inv.status === 'paid' && inv.receipt_no)
      ? `<a class="text-sm font-semibold text-indigo-600 hover:text-indigo-500" href="${receiptUrl}" target="_blank">ใบเสร็จ</a>`
      : (inv.status !== 'paid')
        ? `<a class="text-sm font-semibold text-indigo-600 hover:text-indigo-500" href="${payUrl}">ชำระ</a>`
        : `<a class="text-sm font-semibold text-indigo-600 hover:text-indigo-500" href="${pdfUrl}" target="_blank">ดู</a>`;

    return `
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-sm font-semibold text-slate-900">${inv.invoice_no || '-'} • ${inv.type || '-'} • ${period}</div>
            <div class="mt-1 text-xs text-slate-600">ยอด ${fmtMoney(inv.total)} บาท • ครบกำหนด ${dt(inv.due_date)} • ${statusBadge(inv.status)}</div>
            <div class="mt-2">
              <a class="text-xs font-semibold text-slate-700 hover:text-slate-900" href="${pdfUrl}" target="_blank">เปิดใบแจ้งหนี้</a>
            </div>
          </div>
          <div class="shrink-0">${right}</div>
        </div>
      </div>
    `;
  }

  async function load() {
    const [meRes, sumRes] = await Promise.all([
      window.api.get('/auth/me'),
      window.api.get('/tenant/dashboard/summary'),
    ]);

    // ✅ /auth/me ส่วนใหญ่คืนข้อมูล user ตรง ๆ ไม่ได้ห่อใน user
    const me = meRes.data?.user ?? meRes.data ?? {};
    document.getElementById('me_name').textContent = me.name || me.email || '-';

    const d = sumRes.data || {};

    // ✅ current_room อยู่บนสุด (ไม่ใช่ tenant.current_room)
    const room = d.current_room || null;
    document.getElementById('room_line').textContent = room ? `ห้อง ${room.code} • ${room.status}` : 'ห้อง -';

    // ✅ KPI อยู่ใน summary (และคุณมี key ซ้ำ top-level ด้วย)
    const totalDue = d.summary?.total_due ?? d.total_due ?? 0;
    const unpaidCount = d.summary?.unpaid_invoices ?? d.unpaid_invoices ?? 0;
    const repairOpen = d.summary?.repair_open ?? d.repair_open ?? 0;

    document.getElementById('k_unpaid_total').textContent = fmtMoney(totalDue);
    document.getElementById('k_unpaid_count').textContent = unpaidCount ?? '-';
    document.getElementById('k_repair_open').textContent = repairOpen ?? '-';

    // cleaning ยังไม่ได้ส่งจาก API → แสดง 0 ไว้ก่อน
    document.getElementById('k_clean_open').textContent = 0;

    // ✅ API ตอนนี้ส่ง latest.invoice / latest.repair เป็น null
    // ถ้าคุณอยากใช้ list ต้องให้ API ส่ง latest_invoices, recent_unpaid ก่อน
    const latestInvoices = d.latest_invoices || [];
    const recentUnpaid = d.recent_unpaid || [];

    const invList = document.getElementById('invList');
    if (!latestInvoices.length) {
      document.getElementById('invEmpty').classList.remove('hidden');
      invList.innerHTML = '';
    } else {
      invList.innerHTML = latestInvoices.map(invCard).join('');
    }

    const unpaidList = document.getElementById('unpaidList');
    if (!recentUnpaid.length) {
      document.getElementById('unpaidEmpty').classList.remove('hidden');
      unpaidList.innerHTML = '';
    } else {
      unpaidList.innerHTML = recentUnpaid.map(invCard).join('');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    load().catch(e => alert(e?.response?.data?.message || 'โหลดข้อมูลไม่สำเร็จ'));
  });
</script>

</x-tenant-layout>
