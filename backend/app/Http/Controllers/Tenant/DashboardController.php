<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\RepairRequest;
use App\Models\CleaningRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ✅ ดึง tenant แบบชัวร์เหมือน API (ไม่พึ่ง $user->tenant)
        $tenant = Tenant::where('user_id', $user->id)
            ->with(['currentRoom'])
            ->first();

        // ถ้าไม่มี tenant ให้เด้งไปสร้างโปรไฟล์ (กันพัง)
        if (!$tenant) {
            return redirect()->route('tenant.profile.create');
        }

        // KPI default (กันหน้าไม่ขึ้น)
        $unpaidTotal = 0;
        $unpaidCount = 0;
        $repairOpen  = 0;
        $cleanOpen   = 0;

        $latestInvoices = collect();
        $recentUnpaid   = collect();

        // ✅ กัน 500 กรณีบางตารางยังไม่มี
        if (Schema::hasTable('invoices')) {
            $unpaidTotal = Invoice::where('tenant_id', $tenant->id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->sum('total');

            $unpaidCount = Invoice::where('tenant_id', $tenant->id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->count();

            $latestInvoices = Invoice::where('tenant_id', $tenant->id)
                ->latest()
                ->limit(5)
                ->get();

            $recentUnpaid = Invoice::where('tenant_id', $tenant->id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderByDesc('due_date')
                ->limit(5)
                ->get();
        }

        if (Schema::hasTable('repair_requests')) {
            $repairOpen = RepairRequest::where('tenant_id', $tenant->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();
        }

        // cleaning_requests บางโปรเจกต์ยังไม่มี → กันไว้
        if (class_exists(CleaningRequest::class) && Schema::hasTable((new CleaningRequest)->getTable())) {
            $cleanOpen = CleaningRequest::where('tenant_id', $tenant->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();
        }

        return view('tenant.dashboard', compact(
            'tenant',
            'unpaidTotal', 'unpaidCount', 'repairOpen', 'cleanOpen',
            'latestInvoices', 'recentUnpaid'
        ));
    }
}
