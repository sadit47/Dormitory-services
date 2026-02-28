<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\{Payment, RepairRequest};
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function show(File $file)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        // admin ดูได้ทั้งหมด
        if ($user->role === 'admin') {
            return $this->stream($file);
        }

        // tenant: ต้องเป็นเจ้าของ และต้องผูกกับข้อมูลของตัวเอง
        if ((int)$file->owner_user_id !== (int)$user->id) {
            abort(403);
        }

        // เช็ค ref_type เพิ่มความชัวร์ว่าเป็นของ tenant จริง
        if ($file->ref_type === 'payment') {
            $payment = Payment::with('invoice.tenant')->findOrFail($file->ref_id);
            abort_if($payment->invoice->tenant_id !== $user->tenant->id, 403);
        }

        if ($file->ref_type === 'repair') {
            $repair = RepairRequest::findOrFail($file->ref_id);
            abort_if($repair->tenant_id !== $user->tenant->id, 403);
        }

        return $this->stream($file);
    }

    private function stream(File $file)
    {
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $fullPath = Storage::disk($file->disk)->path($file->path);
        return response()->file($fullPath, [
            'Content-Type' => $file->mime,
        ]);
    }
}
