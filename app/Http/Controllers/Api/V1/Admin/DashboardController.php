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

        return response()->json([
            'kpi' => [
                'rooms_total' => $totalRooms,
                'rooms_occupied' => $occupiedRooms,
                'rooms_vacant' => $vacantRooms,
                'tenant_count' => $tenantCount,
                'income_month' => (float) $incomeMonth,
            ],
            'pending_payments' => $pendingPayments,
            'pending_repairs' => $pendingRepairs,
        ]);
    }
}
