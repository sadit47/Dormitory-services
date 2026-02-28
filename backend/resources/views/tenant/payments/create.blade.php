<x-tenant-layout title="ชำระเงิน">
  <div class="grid gap-6 lg:grid-cols-2">
    <div class="card-strong p-5">
      <div class="font-semibold text-slate-800">ข้อมูลใบแจ้งหนี้</div>
      <div class="mt-3 space-y-2 text-sm">
        <div class="flex items-center justify-between"><span class="text-slate-600">เลขใบแจ้งหนี้</span><span class="font-semibold" id="inv_no">-</span></div>
        <div class="flex items-center justify-between"><span class="text-slate-600">ยอดรวม</span><span class="font-semibold" id="inv_total">-</span></div>
        <div class="flex items-center justify-between"><span class="text-slate-600">ครบกำหนด</span><span class="font-semibold" id="inv_due">-</span></div>
        <div class="flex items-center justify-between"><span class="text-slate-600">สถานะ</span><span class="font-semibold" id="inv_status">-</span></div>
      </div>
      <div class="mt-4 text-xs text-slate-500">แนบสลิป เพื่อให้เจ้าหน้าที่ตรวจสอบ</div>
    </div>

    <div class="card-strong p-5">
      <div class="font-semibold text-slate-800">แจ้งชำระเงิน</div>

      <form id="payForm" class="mt-4 space-y-4">
        <div>
          <label class="text-sm text-slate-700">จำนวนเงิน</label>
          <input name="amount" id="amount" type="number" step="0.01" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm text-slate-700">วันที่/เวลาโอน</label>
          <input name="paid_at" id="paid_at" type="datetime-local" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm text-slate-700">สลิปโอนเงิน</label>
          <input name="slip" id="slip" type="file" accept="image/*" class="mt-1 block w-full text-sm" required>
          <div class="mt-1 text-xs text-slate-500">รองรับรูปภาพเท่านั้น</div>
        </div>

        <div class="flex items-center gap-2">
          <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" type="submit">ส่งแจ้งชำระ</button>
          <a href="/tenant/invoices" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">กลับ</a>
        </div>
      </form>
    </div>
  </div>

  <script>
    const fmtMoney = (n) => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n || 0));
    const dt = (s) => s ? new Date(s).toLocaleString('th-TH') : '-';

    function getInvoiceIdFromUrl() {
      const parts = window.location.pathname.split('/').filter(Boolean);
      // /tenant/payments/create/{invoice}
      return parts[parts.length - 1];
    }

    async function loadInvoice() {
      const id = getInvoiceIdFromUrl();
      const res = await window.api.get(`/tenant/invoices/${id}`);
      const inv = res.data.data;

      document.getElementById('inv_no').textContent = inv.invoice_no || '-';
      document.getElementById('inv_total').textContent = `${fmtMoney(inv.total)} บาท`;
      document.getElementById('inv_due').textContent = dt(inv.due_date);
      document.getElementById('inv_status').textContent = inv.status || '-';

      document.getElementById('amount').value = Number(inv.total || 0).toFixed(2);

      // default paid_at to now
      const now = new Date();
      const pad = (x) => String(x).padStart(2, '0');
      const local = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
      document.getElementById('paid_at').value = local;

      return id;
    }

    document.addEventListener('DOMContentLoaded', () => {
      let invoiceId = null;

      loadInvoice()
        .then((id) => { invoiceId = id; })
        .catch((e) => alert(e?.response?.data?.message || 'โหลดข้อมูลใบแจ้งหนี้ไม่สำเร็จ'));

      document.getElementById('payForm').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        try {
          const fd = new FormData();
          fd.append('amount', document.getElementById('amount').value);
          fd.append('paid_at', document.getElementById('paid_at').value);
          const file = document.getElementById('slip').files?.[0];
          if (file) fd.append('slip', file);

          await window.api.post(`/tenant/payments/${invoiceId}`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });

          alert('ส่งแจ้งชำระแล้ว รอเจ้าหน้าที่ตรวจสอบ');
          window.location.href = '/tenant/invoices';
        } catch (e) {
          alert(e?.response?.data?.message || 'ส่งแจ้งชำระไม่สำเร็จ');
        }
      });
    });
  </script>
</x-tenant-layout>
