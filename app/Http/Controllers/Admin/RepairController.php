<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RepairRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RepairController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $status = $request->query('status');

        $repairs = RepairRequest::with(['tenant.user', 'room', 'files'])
            ->when($q, function ($qr) use ($q) {
                $qr->where('title', 'like', "%{$q}%")
                   ->orWhereHas('tenant.user', function ($u) use ($q) {
                       $u->where('name', 'like', "%{$q}%")
                         ->orWhere('email', 'like', "%{$q}%");
                   })
                   ->orWhereHas('room', function ($r) use ($q) {
                       $r->where('code', 'like', "%{$q}%");
                   });
            })
            ->when($status, fn($qr) => $qr->where('status', $status))
            ->latest('requested_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.repairs.index', compact('repairs', 'q', 'status'));
    }

    public function updateStatus(Request $request, RepairRequest $repair)
    {
        $data = $request->validate([
            'status' => ['required', 'in:submitted,in_progress,done,rejected'],
        ]);

        $repair->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ]);

        return back()->with('success', 'อัปเดตสถานะเรียบร้อย');
    }

    public function destroy(RepairRequest $repair)
{
    $repair->load('files');

    DB::transaction(function () use ($repair) {

        // ลบไฟล์แนบ
        foreach ($repair->files as $f) {
            if (!empty($f->disk) && !empty($f->path)) {
                Storage::disk($f->disk)->delete($f->path);
            }
            $f->delete();
        }

        // ลบรายการแจ้งซ่อม
        $repair->delete();
    });

    return back()->with('success', 'ลบรายการแจ้งซ่อมเรียบร้อย');
}
}
