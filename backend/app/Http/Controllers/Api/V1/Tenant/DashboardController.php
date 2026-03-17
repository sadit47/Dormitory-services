<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RepairRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $u = $request->user();

        try {
            $tenant = Tenant::where('user_id', $u->id)->first();

            if (!$tenant) {
                return apiResponse([
                    'user' => [
                        'id'    => $u->id,
                        'name'  => $u->name,
                        'email' => $u->email,
                        'role'  => $u->role,
                    ],
                    'tenant' => null,
                    'current_room' => null,
                    'summary' => [
                        'total_due'       => 0,
                        'unpaid_invoices' => 0,
                        'repair_open'     => 0,
                    ],
                    'latest_invoices' => [],
                    'recent_unpaid'   => [],
                    'paid_history'    => [],
                    'total_due'       => 0,
                    'unpaid_invoices' => 0,
                    'repair_open'     => 0,
                    'debug' => [
                        'reason'  => 'TENANT_NOT_FOUND_FOR_THIS_USER',
                        'user_id' => $u->id,
                    ],
                ], 'Tenant not found', 200);
            }

            $tenant->load(['currentRoom', 'currentAssignment.room']);
            $currentRoom = $tenant->currentRoom ?? $tenant->currentAssignment?->room;

            $totalDue = 0.0;
            $unpaidCount = 0;
            $repairOpen = 0;
            $latestInvoices = [];
            $recentUnpaid = [];
            $paidHistory = [];

            // =============================
            // INVOICES + PAYMENT STATUS
            // =============================
            try {
                if (Schema::hasTable('invoices')) {
                    $totalDue = (float) Invoice::where('tenant_id', $tenant->id)
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->sum('total');

                    $unpaidCount = (int) Invoice::where('tenant_id', $tenant->id)
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->count();

                    $invoiceCols = [
                        'id',
                        'invoice_no',
                        'type',
                        'period_month',
                        'period_year',
                        'total',
                        'status',
                        'created_at',
                    ];

                    if (Schema::hasColumn('invoices', 'due_date')) {
                        $invoiceCols[] = 'due_date';
                    }

                    if (Schema::hasColumn('invoices', 'room_id')) {
                        $invoiceCols[] = 'room_id';
                    }

                    if (Schema::hasColumn('invoices', 'receipt_no')) {
                        $invoiceCols[] = 'receipt_no';
                    }

                    $roomCols = ['id'];
                    if (Schema::hasTable('rooms')) {
                        if (Schema::hasColumn('rooms', 'code')) $roomCols[] = 'code';
                        if (Schema::hasColumn('rooms', 'room_no')) $roomCols[] = 'room_no';
                        if (Schema::hasColumn('rooms', 'name')) $roomCols[] = 'name';
                    }

                    $roomSelect = implode(',', $roomCols);

                    $latestQuery = Invoice::where('tenant_id', $tenant->id)
                        ->with(["room:$roomSelect"])
                        ->limit(5);

                    if (Schema::hasColumn('invoices', 'created_at')) {
                        $latestQuery->latest();
                    } else {
                        $latestQuery->orderByDesc('id');
                    }

                    $latest = $latestQuery->get($invoiceCols);

                    $recentQuery = Invoice::where('tenant_id', $tenant->id)
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->with(["room:$roomSelect"])
                        ->limit(5);

                    if (Schema::hasColumn('invoices', 'due_date')) {
                        $recentQuery->orderBy('due_date', 'asc');
                    } elseif (Schema::hasColumn('invoices', 'created_at')) {
                        $recentQuery->latest();
                    } else {
                        $recentQuery->orderByDesc('id');
                    }

                    $recent = $recentQuery->get($invoiceCols);

                    /** @var Collection<int,int> $invoiceIds */
                    $invoiceIds = $latest->pluck('id')
                        ->merge($recent->pluck('id'))
                        ->unique()
                        ->values();

                    $paymentStatusByInvoice = [];

                    if (
                        Schema::hasTable('payments') &&
                        Schema::hasColumn('payments', 'invoice_id') &&
                        Schema::hasColumn('payments', 'status') &&
                        Schema::hasColumn('payments', 'created_at') &&
                        $invoiceIds->count() > 0
                    ) {
                        $payments = Payment::whereIn('invoice_id', $invoiceIds->all())
                            ->select('invoice_id', 'status', 'created_at')
                            ->orderBy('invoice_id')
                            ->orderByDesc('created_at')
                            ->get();

                        $paymentStatusByInvoice = $payments
                            ->groupBy('invoice_id')
                            ->map(function ($g) {
                                $first = $g->first();
                                return $first ? $first->status : null;
                            })
                            ->toArray();
                    }

                    $latestInvoices = $latest->map(function (Invoice $inv) use ($paymentStatusByInvoice) {
                        return [
                            'id'             => $inv->id,
                            'invoice_no'     => $inv->invoice_no,
                            'type'           => $inv->type,
                            'period_month'   => $inv->period_month,
                            'period_year'    => $inv->period_year,
                            'total'          => $inv->total,
                            'due_date'       => $inv->getAttribute('due_date'),
                            'status'         => $inv->status,
                            'receipt_no'     => $inv->getAttribute('receipt_no'),
                            'created_at'     => $inv->created_at,
                            'room_id'        => $inv->getAttribute('room_id'),
                            'payment_status' => $paymentStatusByInvoice[$inv->id] ?? null,
                            'room'           => $inv->room ? $inv->room->only(array_keys($inv->room->getAttributes())) : null,
                        ];
                    })->values()->all();

                    $recentUnpaid = $recent->map(function (Invoice $inv) use ($paymentStatusByInvoice) {
                        return [
                            'id'             => $inv->id,
                            'invoice_no'     => $inv->invoice_no,
                            'type'           => $inv->type,
                            'period_month'   => $inv->period_month,
                            'period_year'    => $inv->period_year,
                            'total'          => $inv->total,
                            'due_date'       => $inv->getAttribute('due_date'),
                            'status'         => $inv->status,
                            'receipt_no'     => $inv->getAttribute('receipt_no'),
                            'created_at'     => $inv->created_at,
                            'room_id'        => $inv->getAttribute('room_id'),
                            'payment_status' => $paymentStatusByInvoice[$inv->id] ?? null,
                            'room'           => $inv->room ? $inv->room->only(array_keys($inv->room->getAttributes())) : null,
                        ];
                    })->values()->all();
                }
            } catch (Throwable $e) {
                \Log::warning('TENANT_DASHBOARD_INVOICES_ERROR', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);

                $totalDue = 0.0;
                $unpaidCount = 0;
                $latestInvoices = [];
                $recentUnpaid = [];
            }

            // =============================
            // REPAIR OPEN
            // =============================
            try {
                if (Schema::hasTable('repair_requests')) {
                    $repairOpen = (int) RepairRequest::where('tenant_id', $tenant->id)
                        ->whereIn('status', ['pending', 'in_progress'])
                        ->count();
                }
            } catch (Throwable $e) {
                \Log::warning('TENANT_DASHBOARD_REPAIRS_ERROR', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);

                $repairOpen = 0;
            }

            // =============================
            // PAID HISTORY
            // =============================
            try {
                if (
                    Schema::hasTable('payments') &&
                    Schema::hasColumn('payments', 'status') &&
                    Schema::hasColumn('payments', 'paid_at') &&
                    Schema::hasColumn('payments', 'amount') &&
                    Schema::hasTable('invoices')
                ) {
                    $rows = Payment::whereHas('invoice', function ($q) use ($tenant) {
                            $q->where('tenant_id', $tenant->id);
                        })
                        ->where('status', 'approved')
                        ->whereNotNull('paid_at')
                        ->select(
                            DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as ym"),
                            DB::raw("SUM(amount) as total")
                        )
                        ->groupBy('ym')
                        ->orderBy('ym', 'asc')
                        ->get()
                        ->keyBy('ym');

                    $months = [];
                    $now = now()->startOfMonth();

                    for ($i = 5; $i >= 0; $i--) {
                        $months[] = $now->copy()->subMonths($i)->format('Y-m');
                    }

                    $paidHistory = collect($months)->map(function ($ym) use ($rows) {
                        return [
                            'label'  => $ym,
                            'amount' => (float) ($rows[$ym]->total ?? 0),
                        ];
                    })->values()->toArray();
                }
            } catch (Throwable $e) {
                \Log::warning('TENANT_DASHBOARD_PAID_HISTORY_ERROR', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);

                $paidHistory = [];
            }

            $payload = [
                'user' => [
                    'id'    => $u->id,
                    'name'  => $u->name,
                    'email' => $u->email,
                    'role'  => $u->role,
                ],
                'tenant' => $tenant->only([
                    'id',
                    'citizen_id',
                    'address',
                    'emergency_contact',
                    'start_date',
                    'end_date',
                ]),
                'current_room' => $currentRoom,
                'summary' => [
                    'total_due'       => $totalDue,
                    'unpaid_invoices' => $unpaidCount,
                    'repair_open'     => $repairOpen,
                ],
                'latest_invoices' => $latestInvoices,
                'recent_unpaid'   => $recentUnpaid,
                'paid_history'    => $paidHistory,
                'debug' => [
                    'tenant_id' => $tenant->id,
                    'has_room'  => $currentRoom ? true : false,
                    'current_room_id' => $currentRoom?->id,
                    'current_assignment_room_id' => $tenant->currentAssignment?->room_id,
                    'total_due' => $totalDue,
                    'unpaid_count' => $unpaidCount,
                    'repair_open' => $repairOpen,
                ],
            ];

            // fallback สำหรับ frontend เก่า
            $payload['total_due'] = $totalDue;
            $payload['unpaid_invoices'] = $unpaidCount;
            $payload['repair_open'] = $repairOpen;

            return apiResponse($payload, 'OK');
        } catch (Throwable $e) {
            \Log::error('TENANT_DASHBOARD_SUMMARY_FATAL_ERROR', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return apiResponse([
                'user' => [
                    'id'    => $request->user()?->id,
                    'name'  => $request->user()?->name,
                    'email' => $request->user()?->email,
                    'role'  => $request->user()?->role,
                ],
                'tenant' => null,
                'current_room' => null,
                'summary' => [
                    'total_due'       => 0,
                    'unpaid_invoices' => 0,
                    'repair_open'     => 0,
                ],
                'latest_invoices' => [],
                'recent_unpaid'   => [],
                'paid_history'    => [],
                'debug' => [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                ],
            ], 'Dashboard fallback', 200);
        }
    }
}