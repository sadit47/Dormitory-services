<x-admin-layout title="แดชบอร์ด">
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="card-strong p-4">
      <div class="text-xs font-semibold text-slate-500">ห้องทั้งหมด</div>
      <div class="mt-2 text-2xl font-extrabold text-slate-900" id="k_rooms_total">-</div>
    </div>
    <div class="card-strong p-4">
      <div class="text-xs font-semibold text-slate-500">ห้องมีผู้เช่า</div>
      <div class="mt-2 text-2xl font-extrabold text-slate-900" id="k_rooms_occ">-</div>
    </div>
    <div class="card-strong p-4">
      <div class="text-xs font-semibold text-slate-500">ห้องว่าง</div>
      <div class="mt-2 text-2xl font-extrabold text-slate-900" id="k_rooms_vac">-</div>
    </div>
    <div class="card-strong p-4">
      <div class="text-xs font-semibold text-slate-500">รายรับเดือนนี้</div>
      <div class="mt-2 text-2xl font-extrabold text-slate-900" id="k_income_month">-</div>
    </div>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
      <div class="px-4 py-3 bg-slate-50 text-slate-700 font-semibold">ชำระเงินรอตรวจสอบ</div>
      <div class="p-4">
        <div id="payEmpty" class="hidden text-sm text-slate-600">ไม่มีรายการ</div>
        <div class="space-y-3" id="payList"></div>
        <a href="{{ route('admin.payments.pending') }}"
           class="mt-4 inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">
          ไปหน้าตรวจสอบ
        </a>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
      <div class="px-4 py-3 bg-slate-50 text-slate-700 font-semibold">แจ้งซ่อมล่าสุด</div>
      <div class="p-4">
        <div id="repEmpty" class="hidden text-sm text-slate-600">ไม่มีรายการ</div>
        <div class="space-y-3" id="repList"></div>
        <a href="{{ route('admin.repairs.index') }}"
           class="mt-4 inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">
          ไปหน้าจัดการแจ้งซ่อม
        </a>
      </div>
    </div>
  </div>

  <script>
    const fmtMoney = (n) => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      .format(Number(n || 0));

    const dt = (s) => {
      if (!s) return '-';
      const d = new Date(s);
      return isNaN(d.getTime()) ? String(s) : d.toLocaleString('th-TH');
    };

    const setText = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = (val ?? '-') + '';
    };

    const show = (id) => document.getElementById(id)?.classList.remove('hidden');
    const hide = (id) => document.getElementById(id)?.classList.add('hidden');

    async function loadDashboard() {
      const res = await window.api.get('/admin/dashboard/summary');
      const d = res?.data || {};

      // รองรับหลายรูปแบบ response:
      // 1) { kpi: {...}, pending_payments: [...], pending_repairs: [...] }
      // 2) { data: { kpi: {...}, ... } }
      const payload = d.data ? d.data : d;
      const kpi = payload.kpi || {};

      // รองรับหลายชื่อ field (กันพังหาก API เปลี่ยนชื่อ)
      setText('k_rooms_total',  kpi.rooms_total ?? kpi.total_rooms ?? kpi.rooms ?? '-');
      setText('k_rooms_occ',    kpi.rooms_occupied ?? kpi.occupied_rooms ?? kpi.occupied ?? '-');
      setText('k_rooms_vac',    kpi.rooms_vacant ?? kpi.vacant_rooms ?? kpi.vacant ?? '-');

      const income = kpi.income_month ?? kpi.month_income ?? kpi.income ?? 0;
      setText('k_income_month', fmtMoney(income));

      // Pending Payments
      const pendingPayments = payload.pending_payments || payload.payments_pending || [];
      const payList = document.getElementById('payList');

      if (!Array.isArray(pendingPayments) || pendingPayments.length === 0) {
        show('payEmpty');
        if (payList) payList.innerHTML = '';
      } else {
        hide('payEmpty');
        if (payList) {
          payList.innerHTML = pendingPayments.map(p => {
            const inv = p?.invoice || {};
            const tenant = inv?.tenant || {};
            const user = tenant?.user || {};
            const name = user?.name || user?.email || '-';
            const invoiceNo = inv?.invoice_no || inv?.no || '-';
            const amount = p?.amount ?? inv?.amount_total ?? 0;
            const paidAt = p?.paid_at || p?.created_at || null;

            return `
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-slate-900">${invoiceNo} • ${name}</div>
                    <div class="mt-1 text-xs text-slate-600">ยอด ${fmtMoney(amount)} บาท • ${dt(paidAt)}</div>
                  </div>
                  <a href="{{ route('admin.payments.pending') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">ตรวจสอบ</a>
                </div>
              </div>
            `;
          }).join('');
        }
      }

      // Pending Repairs
      const pendingRepairs = payload.pending_repairs || payload.repairs_pending || [];
      const repList = document.getElementById('repList');

      if (!Array.isArray(pendingRepairs) || pendingRepairs.length === 0) {
        show('repEmpty');
        if (repList) repList.innerHTML = '';
      } else {
        hide('repEmpty');
        if (repList) {
          repList.innerHTML = pendingRepairs.map(r => {
            const tenant = r?.tenant || {};
            const user = tenant?.user || {};
            const name = user?.name || user?.email || '-';
            const roomCode = r?.room?.code || r?.room_code || '-';
            const issue = r?.issue || r?.description || '-';
            const when = r?.requested_at || r?.created_at || null;

            return `
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="text-sm font-semibold text-slate-900">${roomCode} • ${name}</div>
                <div class="mt-1 text-xs text-slate-600">${issue} • ${dt(when)}</div>
              </div>
            `;
          }).join('');
        }
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadDashboard().catch(e => {
        console.error(e);
        const msg =
          e?.response?.data?.message ||
          (e?.response?.status === 401 ? 'หมดอายุการเข้าสู่ระบบ กรุณาเข้าสู่ระบบใหม่' : null) ||
          'โหลดข้อมูลไม่สำเร็จ';
        alert(msg);
      });
    });
  </script>
</x-admin-layout>
