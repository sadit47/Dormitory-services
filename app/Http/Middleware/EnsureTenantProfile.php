<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantProfile
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // บังคับเฉพาะ role tenant เท่านั้น
        if ($user && $user->role === 'tenant' && !$user->tenant) {
            // เด้งไปหน้าโปรไฟล์ (ของ Breeze มีอยู่แล้ว)
            return redirect()->route('tenant.profile.create')
                ->with('success', 'กรุณากรอกข้อมูลผู้เช่าก่อน เพื่อใช้งานระบบ');
        }

        return $next($request);
    }
}
