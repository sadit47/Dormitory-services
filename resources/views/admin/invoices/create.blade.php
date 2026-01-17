{{-- resources/views/admin/invoices/create.blade.php --}}
<x-admin-layout title="ออกใบแจ้งหนี้">
  <div class="card-strong p-6 max-w-5xl">

    <form method="POST" action="{{ route('admin.invoices.store') }}" class="space-y-5">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">ผู้เช่า</label>
          <select id="tenantSelect" name="tenant_id"
                  class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            <option value="">-- เลือกผู้เช่า --</option>
            @foreach($tenants as $t)
              <option value="{{ $t->id }}" @selected(old('tenant_id')==$t->id)>
                {{ $t->user?->name ?? '-' }} ({{ $t->user?->email ?? '-' }})
              </option>
            @endforeach
          </select>
          @error('tenant_id') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        {{-- ✅ ห้อง: auto จากผู้เช่า --}}
        <div>
          <label class="text-sm font-semibold">ห้องปัจจุบัน (อัตโนมัติ)</label>

          {{-- แสดงให้ดู --}}
          <input id="roomDisplay" type="text"
                 class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 bg-slate-100"
                 placeholder="เลือกผู้เช่าเพื่อแสดงห้อง"
                 readonly>

          {{-- ส่งค่าไป backend --}}
          <input type="hidden" name="room_id" id="roomId" value="{{ old('room_id') }}">

          @error('room_id') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror

          <div id="roomHint" class="mt-1 text-xs text-slate-500">
            ระบบจะดึงห้องจาก “ห้องที่ผู้เช่าพักอยู่ตอนนี้”
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="text-sm font-semibold">ประเภท</label>
          <select name="type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            <option value="rent"     @selected(old('type','rent')==='rent')>ค่าเช่า</option>
            <option value="utility"  @selected(old('type')==='utility')>ค่าน้ำ/ค่าไฟ</option>
            <option value="repair"   @selected(old('type')==='repair')>ค่าซ่อม</option>
            <option value="cleaning" @selected(old('type')==='cleaning')>ค่าทำความสะอาด</option>
          </select>
          @error('type') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-semibold">เดือน</label>
          <input type="number" name="period_month" min="1" max="12"
                 value="{{ old('period_month', now()->month) }}"
                 class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
          @error('period_month') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-semibold">ปี</label>
          <input type="number" name="period_year" min="2000" max="2100"
                 value="{{ old('period_year', now()->year) }}"
                 class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
          @error('period_year') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-semibold">กำหนดชำระ</label>
          <input type="date" name="due_date" value="{{ old('due_date') }}"
                 class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
          @error('due_date') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-semibold">ส่วนลด</label>
          <input type="number" step="0.01" name="discount" value="{{ old('discount', 0) }}"
                 class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
          @error('discount') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- Items --}}
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <div class="flex items-center justify-between">
          <div class="font-bold text-slate-900">รายการ</div>
          <button type="button" id="addItem"
                  class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">
            + เพิ่มรายการ
          </button>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full text-sm" id="itemsTable">
            <thead class="bg-slate-50 text-slate-600">
              <tr>
                <th class="text-left px-3 py-2">รายละเอียด</th>
                <th class="text-right px-3 py-2 w-32">จำนวน</th>
                <th class="text-right px-3 py-2 w-40">ราคาต่อหน่วย</th>
                <th class="text-right px-3 py-2 w-40">รวม</th>
                <th class="px-3 py-2 w-24"></th>
              </tr>
            </thead>
            <tbody class="divide-y" id="itemsBody"></tbody>
          </table>
        </div>

        @error('items') <div class="mt-2 text-sm text-rose-600">{{ $message }}</div> @enderror
      </div>

      <div class="flex gap-2">
        <a href="{{ route('admin.invoices.index') }}"
           class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50 transition">
          ย้อนกลับ
        </a>

        <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition">
          บันทึกใบแจ้งหนี้
        </button>
      </div>
    </form>
  </div>

  <script>
    // ===== Items =====
    const body = document.getElementById('itemsBody');
    const addBtn = document.getElementById('addItem');

    function rowTpl(i, desc='', qty='1', price='0') {
      return `
      <tr class="item-row">
        <td class="px-3 py-2">
          <input name="items[${i}][description]" value="${desc}"
                 class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
        </td>
        <td class="px-3 py-2 text-right">
          <input name="items[${i}][qty]" value="${qty}" type="number" step="0.01" min="0.01"
                 class="w-28 text-right rounded-xl border border-slate-300 px-3 py-2 qty" required>
        </td>
        <td class="px-3 py-2 text-right">
          <input name="items[${i}][unit_price]" value="${price}" type="number" step="0.01" min="0"
                 class="w-36 text-right rounded-xl border border-slate-300 px-3 py-2 price" required>
        </td>
        <td class="px-3 py-2 text-right font-semibold amount">0.00</td>
        <td class="px-3 py-2 text-right">
          <button type="button"
            class="inline-flex items-center justify-center rounded-xl bg-rose-600 text-white px-4 py-2 text-sm font-semibold hover:bg-rose-500 transition del">
            ลบ
          </button>
        </td>
      </tr>`;
    }

    function recalc(tr) {
      const qty = parseFloat(tr.querySelector('.qty')?.value || 0);
      const price = parseFloat(tr.querySelector('.price')?.value || 0);
      tr.querySelector('.amount').innerText = (qty * price).toFixed(2);
    }

    function bindRow(tr) {
      tr.querySelector('.qty').addEventListener('input', () => recalc(tr));
      tr.querySelector('.price').addEventListener('input', () => recalc(tr));
      tr.querySelector('.del').addEventListener('click', () => tr.remove());
      recalc(tr);
    }

    let idx = 0;
    function addRow(desc='', qty='1', price='0') {
      const tmp = document.createElement('tbody');
      tmp.innerHTML = rowTpl(idx++, desc, qty, price);
      const tr = tmp.querySelector('tr');
      body.appendChild(tr);
      bindRow(tr);
    }

    addBtn.addEventListener('click', () => addRow());
    addRow();

    // ===== ✅ Auto room by tenant =====
    const tenantSelect = document.getElementById('tenantSelect');
    const roomDisplay  = document.getElementById('roomDisplay');
    const roomId       = document.getElementById('roomId');
    const roomHint     = document.getElementById('roomHint');

    async function loadRoom(tenantId) {
      roomDisplay.value = '';
      roomId.value = '';

      if (!tenantId) {
        roomDisplay.value = '';
        roomHint.innerText = 'ระบบจะดึงห้องจาก “ห้องที่ผู้เช่าพักอยู่ตอนนี้”';
        return;
      }

      try {
        const res = await fetch(`/admin/tenants/${tenantId}/current-room`, {
          headers: { 'Accept': 'application/json' }
        });

        const data = await res.json();

        if (data.room_id) {
          roomDisplay.value = data.room_code;
          roomId.value = data.room_id;
          roomHint.innerText = 'ดึงห้องอัตโนมัติเรียบร้อย';
        } else {
          roomDisplay.value = 'ยังไม่ได้เข้าพัก';
          roomHint.innerText = 'ผู้เช่านี้ยังไม่มีห้องปัจจุบัน';
        }
      } catch (e) {
        roomDisplay.value = 'โหลดห้องไม่สำเร็จ';
        roomHint.innerText = 'เช็ค route / controller current-room';
      }
    }

    tenantSelect.addEventListener('change', () => loadRoom(tenantSelect.value));

    // ✅ ถ้ามี old tenant_id (validate กลับมา) ให้โหลดห้องอัตโนมัติด้วย
    const oldTenantId = @js(old('tenant_id'));
    if (oldTenantId) loadRoom(oldTenantId);
  </script>
</x-admin-layout>
