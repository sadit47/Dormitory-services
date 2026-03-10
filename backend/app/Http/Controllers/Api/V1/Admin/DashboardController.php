<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Room, Tenant, Payment, RepairRequest};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary()
    {
        $now = Carbon::now();

        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $vacantRooms = Room::where('status', 'vacant')->count();
        $tenantCount = Tenant::count();

        $incomeMonth = Payment::where('status', 'approved')
            ->whereYear('paid_at', $now->year)
            ->whereMonth('paid_at', $now->month)
            ->sum('amount');

        $incomeTotal = Payment::where('status', 'approved')
            ->sum('amount');

        /**
         * แยกรายได้ตามหมวด
         * อิงจาก invoice.type
         */
        $incomeRows = Payment::query()
            ->selectRaw('LOWER(COALESCE(invoices.type, "")) as invoice_type, SUM(payments.amount) as total')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('payments.status', 'approved')
            ->groupBy(DB::raw('LOWER(COALESCE(invoices.type, ""))'))
            ->get();

        $incomeCategories = [
            'total'     => (float) $incomeTotal,
            'rent'      => 0.0,
            'utility'   => 0.0,
            'repair'    => 0.0,
            'cleaning'  => 0.0,
            'other'     => 0.0,
        ];

        foreach ($incomeRows as $row) {
            $type = (string) $row->invoice_type;
            $amount = (float) $row->total;

            // ค่าเช่า
            if (in_array($type, [
                'rent',
                'room_rent',
                'monthly_rent',
                'lease',
                'rental',
            ], true)) {
                $incomeCategories['rent'] += $amount;
                continue;
            }

            // ค่าน้ำ/ค่าไฟ/ค่าสาธารณูปโภค
            if (in_array($type, [
                'utility',
                'utilities',
                'water',
                'electric',
                'electricity',
                'water_electric',
                'water_electricity',
                'utility_bill',
            ], true)) {
                $incomeCategories['utility'] += $amount;
                continue;
            }

            // ค่าซ่อมแซม
            if (in_array($type, [
                'repair',
                'repair_fee',
                'maintenance_repair',
                'fix',
            ], true)) {
                $incomeCategories['repair'] += $amount;
                continue;
            }

            // ค่าทำความสะอาด
            if (in_array($type, [
                'cleaning',
                'clean',
                'cleaning_fee',
                'maid',
                'service_cleaning',
            ], true)) {
                $incomeCategories['cleaning'] += $amount;
                continue;
            }

            // อื่น ๆ
            $incomeCategories['other'] += $amount;
        }

        $pendingPayments = Payment::with(['invoice.tenant.user'])
            ->where('status', 'waiting')
            ->latest()
            ->limit(10)
            ->get();

        $pendingRepairs = RepairRequest::with(['tenant.user', 'room'])
            ->whereIn('status', ['pending', 'in_progress'])
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

            'chart_4' => [
                ['label' => 'ห้องทั้งหมด', 'value' => (int) $totalRooms],
                ['label' => 'ผู้เช่าทั้งหมด', 'value' => (int) $tenantCount],
                ['label' => 'ห้องว่าง', 'value' => (int) $vacantRooms],
                ['label' => 'รายได้ทั้งหมด', 'value' => (float) $incomeTotal],
            ],

            'income_categories' => [
                'total'    => (float) $incomeCategories['total'],
                'rent'     => (float) $incomeCategories['rent'],
                'utility'  => (float) $incomeCategories['utility'],
                'repair'   => (float) $incomeCategories['repair'],
                'cleaning' => (float) $incomeCategories['cleaning'],
                'other'    => (float) $incomeCategories['other'],
            ],

            'pending_payments' => $pendingPayments,
            'pending_repairs'  => $pendingRepairs,
        ]);
    }
}