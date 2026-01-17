<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Tenant, Payment, RepairRequest, CleaningRequest};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();

        // เลือกปีจาก query string ?year=2026 ถ้าไม่ส่งมาใช้ปีปัจจุบัน
        $year = (int) $request->query('year', $now->year);

        // KPI
        $kpi = [
            'tenant_count' => Tenant::count(),

            'income_month' => Payment::where('status', 'approved')
                ->whereYear('paid_at', $now->year)
                ->whereMonth('paid_at', $now->month)
                ->sum('amount'),

            'income_year' => Payment::where('status', 'approved')
                ->whereYear('paid_at', $now->year)
                ->sum('amount'),

            'repair_month' => RepairRequest::whereYear('requested_at', $now->year)
                ->whereMonth('requested_at', $now->month)
                ->count(),

            'repair_year' => RepairRequest::whereYear('requested_at', $now->year)->count(),

            'clean_month' => CleaningRequest::whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count(),

            'clean_year' => CleaningRequest::whereYear('created_at', $now->year)->count(),
        ];

        // ตารางรายการล่าสุด
        $pendingPayments = Payment::with(['invoice.tenant.user'])
            ->where('status', 'waiting')
            ->latest()
            ->limit(10)
            ->get();

        $latestRepairs = RepairRequest::with(['tenant.user', 'room'])
            ->latest('requested_at')
            ->limit(10)
            ->get();

        // =========================
        // CHART: สรุปรายเดือน (12 เดือน) ของปีที่เลือก
        // =========================
        $incomeByMonth = DB::table('payments')
            ->selectRaw('MONTH(paid_at) as m, SUM(amount) as total')
            ->where('status', 'approved')
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $year)
            ->groupByRaw('MONTH(paid_at)')
            ->pluck('total', 'm');

        $repairByMonth = DB::table('repair_requests')
            ->selectRaw('MONTH(requested_at) as m, COUNT(*) as total')
            ->whereNotNull('requested_at')
            ->whereYear('requested_at', $year)
            ->groupByRaw('MONTH(requested_at)')
            ->pluck('total', 'm');

        $cleanByMonth = DB::table('cleaning_requests')
            ->selectRaw('MONTH(created_at) as m, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'm');

        $chart = [
            'year' => $year,
            'labels' => collect(range(1, 12))->map(fn($m) => sprintf('%02d', $m))->values(),
            'income' => collect(range(1, 12))->map(fn($m) => (float) ($incomeByMonth[$m] ?? 0))->values(),
            'repairs' => collect(range(1, 12))->map(fn($m) => (int) ($repairByMonth[$m] ?? 0))->values(),
            'cleanings' => collect(range(1, 12))->map(fn($m) => (int) ($cleanByMonth[$m] ?? 0))->values(),
        ];

        return view('admin.dashboard', compact('kpi', 'pendingPayments', 'latestRepairs', 'chart'));
    }
}
