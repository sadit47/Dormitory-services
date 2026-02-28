<x-admin-layout title="แก้ไขใบแจ้งหนี้">
  <div class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <div class="text-sm text-slate-600" id="invNo">...</div>
        <div class="text-xs text-slate-500" id="invInfo"></div>
      </div>
      <div class="flex gap-2">
        <a id="btnPdfInvoice" target="_blank" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50" href="#">PDF ใบแจ้งหนี้</a>
        <a id="btnPdfReceipt" target="_blank" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50 hidden" href="#">PDF ใบเสร็จ</a>
      </div>
    </div>

    <form id="frm" class="space-y-5 mt-5">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-semibold">กำหนดชำระ</label>
          <input id="due_date" type="date" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          <div class="mt-1 text-sm text-rose-600" id="err_due_date"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">ส่วนลด</label>
          <input id="discount" type="number" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" value="0" />
          <div class="mt-1 text-sm text-rose-600" id="err_discount"></div>
        </div>
        <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
          <div class="text-xs font-semibold text-slate-500">รวมทั้งสิ้น</div>
          <div class="text-2xl font-extrabold text-slate-900" id="grand">0.00</div>
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-extrabold text-slate-900">รายการ</h3>
          <button type="button" id="btnAdd" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">+ เพิ่มรายการ</button>
        </div>
        <div class="mt-3 space-y-3" id="items"></div>
        <div class="mt-1 text-sm text-rose-600" id="err_items"></div>
      </div>

      <div class="flex gap-2">
        <a href="{{ route('admin.invoices.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">ย้อนกลับ</a>
        <button id="btnSave" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">บันทึก</button>
        <button type="button" id="btnDel" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">ลบใบแจ้งหนี้</button>
      </div>
    </form>
  </div>

  <script>
    const INVOICE_ID = @json($invoiceId);
    const fmt = (n) => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n||0));
    const clearErr = () => ['due_date','discount','items'].forEach(k => (document.getElementById('err_'+k).textContent=''));
    const showErr = (errs) => {
      if (!errs) return;
      Object.entries(errs).forEach(([k,v]) => {
        const el = document.getElementById('err_'+k);
        if (el) el.textContent = Array.isArray(v) ? v[0] : String(v);
      });
    };

    let invoice = null;

    function itemRow(it = {}) {
      const id = crypto.randomUUID();
      return `
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end" data-row="${id}">
          <div class="lg:col-span-6">
            <label class="text-xs font-semibold text-slate-600">รายละเอียด</label>
            <input data-k="description" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" value="${it.description || ''}" />
          </div>
          <div class="lg:col-span-2">
            <label class="text-xs font-semibold text-slate-600">จำนวน</label>
            <input data-k="qty" type="number" step="0.01" min="0.01" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" value="${it.qty ?? 1}" />
          </div>
          <div class="lg:col-span-2">
            <label class="text-xs font-semibold text-slate-600">ราคา/หน่วย</label>
            <input data-k="unit_price" type="number" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" value="${it.unit_price ?? 0}" />
          </div>
          <div class="lg:col-span-1">
            <div class="text-xs font-semibold text-slate-600">รวม</div>
            <div class="mt-2 text-sm font-extrabold text-slate-900" data-k="amount">0.00</div>
          </div>
          <div class="lg:col-span-1 text-right">
            <button type="button" data-delrow="${id}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">ลบ</button>
          </div>
        </div>
      `;
    }

    function recalc() {
      let subtotal = 0;
      document.querySelectorAll('#items [data-row]').forEach(row => {
        const qty = Number(row.querySelector('[data-k="qty"]').value || 0);
        const up = Number(row.querySelector('[data-k="unit_price"]').value || 0);
        const amt = Math.round(qty * up * 100) / 100;
        subtotal += amt;
        row.querySelector('[data-k="amount"]').textContent = fmt(amt);
      });
      const discount = Number(document.getElementById('discount').value || 0);
      const total = Math.max(0, Math.round((subtotal - discount) * 100) / 100);
      document.getElementById('grand').textContent = fmt(total);
    }

    async function loadInvoice() {
      const res = await window.api.get(`/admin/invoices/${INVOICE_ID}`);
      invoice = res.data.data;

      document.getElementById('invNo').textContent = invoice.invoice_no;
      const tenantName = invoice.tenant?.user?.name || invoice.tenant?.user?.email || '-';
      document.getElementById('invInfo').textContent = `${tenantName} • ${invoice.type} • สถานะ: ${invoice.status}`;

      document.getElementById('due_date').value = invoice.due_date || '';
      document.getElementById('discount').value = invoice.discount || 0;

      document.getElementById('btnPdfInvoice').href = `/admin/invoices/${invoice.id}/pdf`;
      if (invoice.status === 'paid' && invoice.receipt_no) {
        const r = document.getElementById('btnPdfReceipt');
        r.href = `/admin/invoices/${invoice.id}/receipt`;
        r.classList.remove('hidden');
      }

      const items = document.getElementById('items');
      items.innerHTML = '';
      (invoice.items || []).forEach(it => items.insertAdjacentHTML('beforeend', itemRow(it)));
      if (!invoice.items?.length) items.insertAdjacentHTML('beforeend', itemRow({ qty: 1, unit_price: 0 }));

      bindItemEvents();
      recalc();

      if (invoice.status === 'paid') {
        document.querySelectorAll('#frm input, #frm button, #frm select').forEach(el => {
          if (el.id === 'btnPdfInvoice' || el.id === 'btnPdfReceipt') return;
          el.disabled = true;
        });
        document.getElementById('btnSave').textContent = 'ชำระแล้ว (แก้ไขไม่ได้)';
      }
    }

    function bindItemEvents() {
      document.querySelectorAll('#items [data-delrow]').forEach(btn => {
        btn.addEventListener('click', () => {
          document.querySelector(`#items [data-row="${btn.dataset.delrow}"]`)?.remove();
          recalc();
        });
      });
      document.querySelectorAll('#items input').forEach(i => i.addEventListener('input', recalc));
      document.getElementById('discount').addEventListener('input', recalc);
    }

    function collectItems() {
      const items = [];
      document.querySelectorAll('#items [data-row]').forEach(row => {
        items.push({
          description: row.querySelector('[data-k="description"]').value.trim(),
          qty: Number(row.querySelector('[data-k="qty"]').value || 0),
          unit_price: Number(row.querySelector('[data-k="unit_price"]').value || 0),
        });
      });
      return items;
    }

    document.addEventListener('DOMContentLoaded', async () => {
      await loadInvoice();

      document.getElementById('btnAdd').addEventListener('click', () => {
        document.getElementById('items').insertAdjacentHTML('beforeend', itemRow({ qty: 1, unit_price: 0 }));
        bindItemEvents();
        recalc();
      });

      document.getElementById('btnDel').addEventListener('click', async () => {
        if (!confirm('ยืนยันลบใบแจ้งหนี้นี้?')) return;
        try {
          await window.api.delete(`/admin/invoices/${INVOICE_ID}`);
          location.href = '{{ route('admin.invoices.index') }}';
        } catch (e) {
          alert(e?.response?.data?.message || 'ลบไม่สำเร็จ');
        }
      });

      document.getElementById('frm').addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErr();
        const payload = {
          due_date: document.getElementById('due_date').value || null,
          discount: Number(document.getElementById('discount').value || 0),
          items: collectItems(),
        };
        try {
          await window.api.put(`/admin/invoices/${INVOICE_ID}`, payload);
          location.href = '{{ route('admin.invoices.index') }}';
        } catch (err) {
          const d = err?.response?.data;
          showErr(d?.errors);
          alert(d?.message || 'บันทึกไม่สำเร็จ');
        }
      });
    });
  </script>
</x-admin-layout>
