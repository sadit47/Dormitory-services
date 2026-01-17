<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, RepairRequest, CleaningRequest};
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tenant = $user->tenant; // มี middleware กันไว้แล้ว

        // KPI
        $unpaidTotal = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['unpaid','partial'])
            ->sum('total');

        $unpaidCount = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['unpaid','partial'])
            ->count();

        $repairOpen = RepairRequest::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending','in_progress'])
            ->count();

        $cleanOpen = CleaningRequest::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending','in_progress'])
            ->count();

        // Lists
        $latestInvoices = Invoice::where('tenant_id', $tenant->id)
            ->latest()->limit(5)->get();

        $recentUnpaid = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['unpaid','partial'])
            ->orderByDesc('due_date')
            ->limit(5)->get();

        return view('tenant.dashboard', compact(
            'tenant',
            'unpaidTotal','unpaidCount','repairOpen','cleanOpen',
            'latestInvoices','recentUnpaid'
        ));
    }
}
