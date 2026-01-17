{{-- resources/views/admin/invoices/edit.blade.php --}}
<x-admin-layout title="แก้ไขใบแจ้งหนี้">
  <div class="card-strong p-6 max-w-5xl">

    <div class="flex items-start justify-between gap-4 mb-5">
      <div>
        <div class="text-sm text-slate-500">Invoice</div>
        <div class="text-xl font-black text-slate-900">{{ $invoice->invoice_no }}</div>
        <div class="text-sm text-slate-600">
          ผู้เช่า: <span class="font-semibold">{{ $invoice->tenant?->user?->name ?? '-' }}</span>
          <span class="text-slate-400">•</span>
          ห้อง: <span class="font-semibold">{{ $invoice->room?->code ?? '-' }}</span>
        </div>
      </div>

      <a href="{{ route('admin.invoices.index') }}"
         class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50 transition">
        ย้อนกลับ
      </a>
    </div>

    @if($invoice->status === 'paid')
      <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800">
        ใบนี้ชำระแล้ว (paid) ระบบจะไม่อนุญาตให้บันทึกการแก้ไข
      </div>
    @endif

    <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="space-y-5">
      @csrf
      @method('PUT')

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-semibold">กำหนดชำระ</label>
          <input type="date" name="due_date"
                 value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}"
                 class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
          @error('due_date') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-semibold">ส่วนลด</label>
          <input type="number" step="0.01" name="discount"
                 value="{{ old('discount', $invoice->discount) }}"
                 class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
          @error('discount') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4">
          <div class="text-sm text-slate-500">ยอดรวมปัจจุบัน</div>
          <div class="text-lg font-black text-slate-900">{{ number_format($invoice->total, 2) }}</div>
          <div class="text-xs text-slate-500">ระบบจะคำนวณใหม่เมื่อกดบันทึก</div>
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
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
              <tr>
                <th class="text-left px-3 py-2">รายละเอียด</th>
                <th class="text-right px-3 py-2 w-32">จำนวน</th>
                <th class="text-right px-3 py-2 w-40">ราคาต่อหน่วย</th>
                <th class="text-right px-3 py-2 w-40">รวม</th>
                <th class="px-3 py-2 w-24"></th>
              </tr>
            </thead>

            <tbody class="divide-y" id="itemsBody">
              @php
                $items = old('items') ?? $invoice->items->map(fn($it)=>[
                  'description' => $it->description,
                  'qty' => $it->qty,
                  'unit_price' => $it->unit_price,
                ])->toArray();
              @endphp

              @foreach($items as $i => $it)
                @php
                  $desc = $it['description'] ?? '';
                  $qty = $it['qty'] ?? 1;
                  $price = $it['unit_price'] ?? 0;
                @endphp
                <tr class="item-row">
                  <td class="px-3 py-2">
                    <input name="items[{{ $i }}][description]" value="{{ $desc }}"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    @error("items.$i.description") <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
                  </td>

                  <td class="px-3 py-2 text-right">
                    <input name="items[{{ $i }}][qty]" value="{{ $qty }}" type="number" step="0.01" min="0.01"
                           class="w-28 text-right rounded-xl border border-slate-300 px-3 py-2 qty" required>
                    @error("items.$i.qty") <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
                  </td>

                  <td class="px-3 py-2 text-right">
                    <input name="items[{{ $i }}][unit_price]" value="{{ $price }}" type="number" step="0.01" min="0"
                           class="w-36 text-right rounded-xl border border-slate-300 px-3 py-2 price" required>
                    @error("items.$i.unit_price") <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
                  </td>

                  <td class="px-3 py-2 text-right font-semibold amount">0.00</td>

                  <td class="px-3 py-2 text-right">
                    <button type="button"
                      class="inline-flex items-center justify-center rounded-xl bg-rose-600 text-white px-4 py-2 text-sm font-semibold hover:bg-rose-500 transition del">
                      ลบ
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @error('items') <div class="mt-2 text-sm text-rose-600">{{ $message }}</div> @enderror
      </div>

      <div class="flex gap-2">
        <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition">
          บันทึกการแก้ไข
        </button>

        <a href="{{ route('admin.invoices.pdf', $invoice) }}"
           class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition">
          ดู PDF
        </a>

        <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}"
              onsubmit="return confirm('ลบใบแจ้งหนี้นี้?')" class="ml-auto">
          @csrf @method('DELETE')
          <button type="submit"
                  class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-500 transition">
            ลบใบแจ้งหนี้
          </button>
        </form>
      </div>
    </form>
  </div>

  <script>
    const body = document.getElementById('itemsBody');
    const addBtn = document.getElementById('addItem');

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

    // bind existing rows
    [...document.querySelectorAll('#itemsBody .item-row')].forEach(bindRow);

    function nextIndex() {
      const inputs = [...document.querySelectorAll('#itemsBody input[name^="items["]')];
      let max = -1;
      inputs.forEach(i => {
        const m = i.name.match(/^items\[(\d+)\]/);
        if (m) max = Math.max(max, parseInt(m[1], 10));
      });
      return max + 1;
    }

    function rowTpl(i) {
      return `
      <tr class="item-row">
        <td class="px-3 py-2">
          <input name="items[${i}][description]" value=""
                 class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
        </td>
        <td class="px-3 py-2 text-right">
          <input name="items[${i}][qty]" value="1" type="number" step="0.01" min="0.01"
                 class="w-28 text-right rounded-xl border border-slate-300 px-3 py-2 qty" required>
        </td>
        <td class="px-3 py-2 text-right">
          <input name="items[${i}][unit_price]" value="0" type="number" step="0.01" min="0"
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

    addBtn.addEventListener('click', () => {
      const i = nextIndex();
      const tmp = document.createElement('tbody');
      tmp.innerHTML = rowTpl(i);
      const tr = tmp.querySelector('tr');
      body.appendChild(tr);
      bindRow(tr);
    });
  </script>
</x-admin-layout>
