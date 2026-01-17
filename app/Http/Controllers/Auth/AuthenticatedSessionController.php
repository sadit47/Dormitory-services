<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // ถ้าคุณจะใช้หน้า role-login เป็นหลัก อันนี้จะยังคงอยู่ได้
        // แต่แนะนำให้ redirect ไปหน้าเลือก role หรือหน้า tenant/login ตามต้องการ
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // login ตาม Breeze
        $request->authenticate();

        // ✅ ตรวจ role จาก hidden input (admin/tenant)
        $expectedRole = $request->input('role'); // 'admin' | 'tenant' | null

        if (!empty($expectedRole)) {
            $userRole = Auth::user()->role ?? null;

            if ($userRole !== $expectedRole) {
                // ❌ role ไม่ตรง -> logout แล้วตีกลับ
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'บัญชีนี้ไม่มีสิทธิ์เข้าสู่หน้านี้',
                ])->onlyInput('email');
            }
        }

        // ปกติ regenerate session
        $request->session()->regenerate();

        // ✅ ไป home (ระบบคุณ redirect ตาม role อยู่แล้ว)
        return redirect()->intended(route('home'));
        // ถ้าจะใช้ของเดิมก็ได้:
        // return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
