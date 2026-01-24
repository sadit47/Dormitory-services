<x-admin-layout title="สร้างใบแจ้งหนี้">
  <div class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form id="frm" class="space-y-5">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-semibold">ผู้เช่า</label>
          <select id="tenant_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"></select>
          <div class="mt-1 text-sm text-rose-600" id="err_tenant_id"></div>
        </div>

        <div>
          <label class="text-sm font-semibold">ห้อง (ถ้ามี)</label>
          <select id="room_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"></select>
          <div class="mt-1 text-sm text-rose-600" id="err_room_id"></div>
        </div>

        <div>
          <label class="text-sm font-semibold">ประเภท</label>
          <select id="type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
            <option value="rent">ค่าเช่า</option>
            <option value="utility">ค่าน้ำ/ไฟ</option>
            <option value="repair">ค่าซ่อม</option>
            <option value="cleaning">ค่าทำความสะอาด</option>
          </select>
          <div class="mt-1 text-sm text-rose-600" id="err_type"></div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div>
          <label class="text-sm font-semibold">เดือน</label>
          <input id="period_month" type="number" min="1" max="12" value="{{ now()->month }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          <div class="mt-1 text-sm text-rose-600" id="err_period_month"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">ปี</label>
          <input id="period_year" type="number" min="2000" max="2100" value="{{ now()->year }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          <div class="mt-1 text-sm text-rose-600" id="err_period_year"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">วันครบกำหนด</label>
          <input id="due_date" type="date" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          <div class="mt-1 text-sm text-rose-600" id="err_due_date"></div>
        </div>
        <div>
          <label class="text-sm font-semibold">ส่วนลด</label>
          <input id="discount" type="number" min="0" step="0.01" value="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          <div class="mt-1 text-sm text-rose-600" id="err_discount"></div>
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between">
          <div class="text-sm font-extrabold text-slate-900">รายการ</div>
          <button type="button" id="btnAdd" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50">+ เพิ่มรายการ</button>
        </div>

        <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
              <tr>
                <th class="px-4 py-3 text-left font-semibold">รายละเอียด</th>
                <th class="px-4 py-3 text-right font-semibold">จำนวน</th>
                <th class="px-4 py-3 text-right font-semibold">ราคา/หน่วย</th>
                <th class="px-4 py-3 text-right font-semibold">รวม</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody id="items" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
        <div class="mt-1 text-sm text-rose-600" id="err_items"></div>
      </div>

      <div class="flex gap-2">
        <a href="{{ route('admin.invoices.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">ย้อนกลับ</a>
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">บันทึก</button>
      </div>
    </form>
  </div>

  <script>
    const clearErr = () => ['tenant_id','room_id','type','period_month','period_year','due_date','discount','items'].forEach(k => {
      const el = document.getElementById('err_'+k); if (el) el.textContent='';
    });
    const showErr = (errs) => {
      if (!errs) return;
      Object.entries(errs).forEach(([k,v]) => {
        const el = document.getElementById('err_'+k);
        if (el) el.textContent = Array.isArray(v) ? v[0] : String(v);
      });
    };

    const fmt = (n) => new Intl.NumberFormat('th-TH', { minimumFractionDigits:2, maximumFractionDigits:2 }).format(Number(n||0));

    function rowTpl(it) {
      return `
        <tr>
          <td class="px-4 py-3"><input class="desc w-full rounded-xl border border-slate-200 px-3 py-2" value="${it.description||''}" placeholder="เช่น ค่าเช่าเดือน..." /></td>
          <td class="px-4 py-3 text-right"><input type="number" step="0.01" min="0.01" class="qty w-24 rounded-xl border border-slate-200 px-3 py-2 text-right" value="${it.qty||1}" /></td>
          <td class="px-4 py-3 text-right"><input type="number" step="0.01" min="0" class="price w-28 rounded-xl border border-slate-200 px-3 py-2 text-right" value="${it.unit_price||0}" /></td>
          <td class="px-4 py-3 text-right font-semibold"><span class="sum">${fmt((it.qty||0)*(it.unit_price||0))}</span></td>
          <td class="px-4 py-3 text-right"><button type="button" class="btnDel rounded-xl bg-slate-900 px-3 py-2 text-white text-sm font-semibold hover:bg-black">ลบ</button></td>
        </tr>
      `;
    }

    function bindItemEvents() {
      document.querySelectorAll('#items tr').forEach(tr => {
        const calc = () => {
          const qty = Number(tr.querySelector('.qty').value||0);
          const price = Number(tr.querySelector('.price').value||0);
          tr.querySelector('.sum').textContent = fmt(qty*price);
        };
        tr.querySelector('.qty').addEventListener('input', calc);
        tr.querySelector('.price').addEventListener('input', calc);
        tr.querySelector('.btnDel').addEventListener('click', () => {
          tr.remove();
        });
      });
    }

    async function loadMeta() {
      const res = await window.api.get('/admin/invoices/meta');
      const tenantSel = document.getElementById('tenant_id');
      tenantSel.innerHTML = '<option value="">-- เลือกผู้เช่า --</option>';
      res.data.tenants.forEach(t => {
        const name = t.user?.name || t.user?.email;
        const o = document.createElement('option');
        o.value = t.id; o.textContent = name;
        tenantSel.appendChild(o);
      });

      const roomSel = document.getElementById('room_id');
      roomSel.innerHTML = '<option value="">-- ไม่ระบุห้อง --</option>';
      res.data.rooms.forEach(r => {
        const o = document.createElement('option');
        o.value = r.id; o.textContent = r.code;
        roomSel.appendChild(o);
      });
    }

    document.addEventListener('DOMContentLoaded', async () => {
      await loadMeta();
      // default 1 item
      document.getElementById('items').innerHTML = rowTpl({ description:'', qty:1, unit_price:0 });
      bindItemEvents();

      document.getElementById('btnAdd').addEventListener('click', () => {
        document.getElementById('items').insertAdjacentHTML('beforeend', rowTpl({ description:'', qty:1, unit_price:0 }));
        bindItemEvents();
      });

      document.getElementById('frm').addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErr();

        const items = Array.from(document.querySelectorAll('#items tr')).map(tr => ({
          description: tr.querySelector('.desc').value.trim(),
          qty: Number(tr.querySelector('.qty').value),
          unit_price: Number(tr.querySelector('.price').value),
        }));

        const payload = {
          tenant_id: Number(document.getElementById('tenant_id').value),
          room_id: document.getElementById('room_id').value ? Number(document.getElementById('room_id').value) : null,
          type: document.getElementById('type').value,
          period_month: Number(document.getElementById('period_month').value),
          period_year: Number(document.getElementById('period_year').value),
          due_date: document.getElementById('due_date').value || null,
          discount: Number(document.getElementById('discount').value||0),
          items,
        };

        try {
          await window.api.post('/admin/invoices', payload);
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
