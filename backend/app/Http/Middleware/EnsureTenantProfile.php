<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantProfile
{
    public function handle(Request $request, Closure $next)
    {
    $user = $request->user();

    if ($user && $user->role === 'tenant' && !$user->tenant) {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tenant profile is required.',
                'code' => 'TENANT_PROFILE_REQUIRED',
            ], 409);
        }

        return redirect()->route('tenant.profile.create')
            ->with('success', 'กรุณากรอกข้อมูลผู้เช่าก่อน เพื่อใช้งานระบบ');
    }

    return $next($request);
    }

}
