{{-- resources/views/admin/dashboard.blade.php --}}
<x-admin-layout title="Dashboard">
  {{-- Filter Year --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div class="text-sm text-slate-500">
      สรุปภาพรวมระบบ + รายงานรายเดือน/รายปี
    </div>

    <form method="GET" class="flex items-center gap-2">
      <span class="text-sm text-slate-600 font-semibold">ปี</span>
      <input name="year" value="{{ $chart['year'] ?? now()->year }}"
             class="w-28 rounded-xl border border-slate-500 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
      <button class="rounded-xl bg-indigo-700 text-white px-4 py-2 text-sm font-semibold hover:bg-indigo-600">
        ดูรายงาน
      </button>
    </form>
  </div>

  {{-- KPI Cards --}}
  <div class="card-strong overflow-hidden">
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
      <div class="text-sm text-slate-500">จำนวนผู้เช่า</div>
      <div class="mt-1 text-3xl font-black">{{ $kpi['tenant_count'] }}</div>
      <div class="mt-3 text-xs text-slate-400">ดู/เพิ่ม/ค้นหาผู้เช่าได้จากเมนู “ผู้เช่า”</div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
      <div class="text-sm text-slate-500">รายรับเดือนนี้</div>
      <div class="mt-1 text-3xl font-black">{{ number_format($kpi['income_month'],2) }}</div>
      <div class="mt-2 text-sm text-slate-600">รายรับทั้งปี: <b>{{ number_format($kpi['income_year'],2) }}</b></div>
      <div class="mt-3 text-xs text-slate-400">นับจาก Payment ที่ approved</div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
      <div class="text-sm text-slate-500">แจ้งซ่อม</div>
      <div class="mt-1 text-3xl font-black">{{ $kpi['repair_month'] }}</div>
      <div class="mt-2 text-sm text-slate-600">ทั้งปี: <b>{{ $kpi['repair_year'] }}</b></div>
      <div class="mt-3 text-xs text-slate-400">ดูรูปภาพ/อัปเดตสถานะได้ (ต่อหน้า list)</div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
      <div class="text-sm text-slate-500">ทำความสะอาด</div>
      <div class="mt-1 text-3xl font-black">{{ $kpi['clean_month'] }}</div>
      <div class="mt-2 text-sm text-slate-600">ทั้งปี: <b>{{ $kpi['clean_year'] }}</b></div>
      <div class="mt-3 text-xs text-slate-400">สรุปจำนวนคำร้องรายเดือน</div>
    </div>
  </div>
  </div>

  {{-- Quick Actions --}}
  <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
      <div class="font-bold mb-3">ทางลัด (Quick actions)</div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <a href="{{ route('admin.tenants.create') }}"
           class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold hover:bg-slate-800 text-center">
          + เพิ่มผู้เช่า
        </a>
        <a href="{{ route('admin.rooms.create') }}"
           class="px-4 py-2 rounded-xl border bg-slate-900 text-white font-semibold hover:bg-slate-800 text-center">
          + เพิ่มห้องพัก
        </a>
        <a href="{{ route('admin.invoices.index') }}"
           class="px-4 py-2 rounded-xl border bg-slate-900 text-white font-semibold hover:bg-slate-800 text-center">
          ออกใบแจ้งหนี้/ใบเสร็จ
        </a>
        <a href="{{ route('admin.payments.pending') }}"
           class="px-4 py-2 rounded-xl border bg-slate-900 text-white font-semibold hover:bg-slate-800 text-center">
          ตรวจสอบการชำระเงิน
        </a>
      </div>
    </div>

    {{-- Charts --}}
    <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
        <div>
          <div class="font-bold">รายงานรายเดือน</div>
          <div class="text-sm text-slate-500">ปี {{ $chart['year'] ?? now()->year }}</div>
        </div>
        <div class="text-xs text-slate-400">
          รายรับ = payments approved
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-slate-200 p-4">
          <div class="text-sm font-semibold text-slate-600 mb-2">รายรับ</div>
          <canvas id="incomeChart" height="150"></canvas>
        </div>
        <div class="rounded-2xl border border-slate-200 p-4">
          <div class="text-sm font-semibold text-slate-600 mb-2">แจ้งซ่อม / ทำความสะอาด</div>
          <canvas id="workChart" height="150"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Tables --}}
  
  <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="card-strong overflow-hidden">
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
      <div class="flex items-center justify-between mb-3">
        <div class="font-bold">รายการชำระเงินรอตรวจสอบ</div>
        <a href="{{ route('admin.payments.pending') }}" class="rounded-xl bg-indigo-700 text-white px-4 py-2 text-sm font-semibold hover:bg-indigo-600">
          ดูทั้งหมด
        </a>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="text-slate-500">
          <tr>
            <th class="text-left py-2">Invoice</th>
            <th class="text-left py-2">ผู้เช่า</th>
            <th class="text-right py-2">ยอด</th>
            <th class="text-left py-2">สถานะ</th>
          </tr>
          </thead>
          <tbody class="text-slate-700">
          @forelse($pendingPayments as $p)
            <tr class="border-t">
              <td class="py-2">{{ $p->invoice?->invoice_no ?? '-' }}</td>
              <td class="py-2">{{ $p->invoice?->tenant?->user?->name ?? '-' }}</td>
              <td class="py-2 text-right">{{ number_format($p->amount,2) }}</td>
              <td class="py-2">
                <span class="px-2 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold">waiting</span>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="py-4 text-slate-400">ไม่มีรายการ</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
    </div>
    
    

    <div class="card-strong overflow-hidden">
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5">
      <div class="flex items-center justify-between mb-3">
        <div class="font-bold">แจ้งซ่อมล่าสุด</div>
        <div class="text-xs text-slate-400">ดูรูป/เปลี่ยนสถานะได้จากหน้ารายการ</div>
      </div>
      

      <div class="space-y-2">
        @forelse($latestRepairs as $r)
          <div class="rounded-2xl border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-semibold truncate">{{ $r->title }}</div>
                <div class="text-xs text-slate-500 mt-1">
                  {{ $r->tenant?->user?->name ?? '-' }} • ห้อง {{ $r->room?->code ?? '-' }}
                  • {{ $r->requested_at?->format('d/m/Y H:i') }}
                </div>
              </div>
              <span class="text-xs px-2 py-1 rounded-full bg-indigo-100 text-indigo-800 font-semibold shrink-0">
                {{ $r->status }}
              </span>
            </div>
          </div>
        @empty
          <div class="text-slate-400">ไม่มีรายการ</div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- Chart.js --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const labels    = @json($chart['labels'] ?? []);
    const income    = @json($chart['income'] ?? []);
    const repairs   = @json($chart['repairs'] ?? []);
    const cleanings = @json($chart['cleanings'] ?? []);

    if (document.getElementById('incomeChart') && labels.length) {
      new Chart(document.getElementById('incomeChart'), {
        type: 'line',
        data: { labels, datasets: [{ label: 'Income', data: income, tension: 0.3, fill: true }]},
        options: { responsive: true }
      });
    }

    if (document.getElementById('workChart') && labels.length) {
      new Chart(document.getElementById('workChart'), {
        type: 'bar',
        data: {
          labels,
          datasets: [
            { label: 'Repairs', data: repairs },
            { label: 'Cleanings', data: cleanings },
          ]
        },
        options: { responsive: true }
      });
    }
  </script>
</x-admin-layout>
