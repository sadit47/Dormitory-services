<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Room, Tenant, Payment, RepairRequest};
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary()
    {
        $now = Carbon::now();

        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status','occupied')->count();
        $vacantRooms = Room::where('status','vacant')->count();
        $tenantCount = Tenant::count();

        $incomeMonth = Payment::where('status','approved')
            ->whereYear('paid_at', $now->year)
            ->whereMonth('paid_at', $now->month)
            ->sum('amount');

        // ✅ รายได้ทั้งหมด
        $incomeTotal = Payment::where('status','approved')
            ->sum('amount');

        $pendingPayments = Payment::with(['invoice.tenant.user'])
            ->where('status','waiting')
            ->latest()
            ->limit(10)
            ->get();

        $pendingRepairs = RepairRequest::with(['tenant.user','room'])
            ->whereIn('status', ['pending','in_progress'])
            ->latest('requested_at')
            ->limit(10)
            ->get();

        return apiResponse([
            'kpi' => [
                'rooms_total'     => (int) $totalRooms,
                'rooms_occupied'  => (int) $occupiedRooms,
                'rooms_vacant'    => (int) $vacantRooms,
                'tenant_count'    => (int) $tenantCount,
                'income_month'    => (float) $incomeMonth,
                'income_total'    => (float) $incomeTotal,
            ],

            // ✅ ใช้ทำกราฟ 4 ค่า (BarChart)
            'chart_4' => [
                ['label' => 'ห้องทั้งหมด',  'value' => (int) $totalRooms],
                ['label' => 'ผู้เช่าทั้งหมด', 'value' => (int) $tenantCount],
                ['label' => 'ห้องว่าง',     'value' => (int) $vacantRooms],
                ['label' => 'รายได้ทั้งหมด', 'value' => (float) $incomeTotal],
            ],

            'pending_payments' => $pendingPayments,
            'pending_repairs'  => $pendingRepairs,
        ]);
    }
}
