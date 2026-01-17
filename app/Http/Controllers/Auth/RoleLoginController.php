<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RoleLoginController extends Controller
{
    // หน้าเลือก role
    public function choose(): View
    {
        return view('auth.choose-role');
    }

    // หน้า login admin
    public function admin(): View
    {
        return view('auth.login-role', ['role' => 'admin']);
    }

    // หน้า login tenant
    public function tenant(): View
    {
        return view('auth.login-role', ['role' => 'tenant']);
    }

    // POST /login (ตรวจ role ก่อน authenticate)
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate(); // Breeze authenticate

        // ✅ เช็ค role จากฟอร์ม
        $expectedRole = $request->input('role'); // admin|tenant|null

        if ($expectedRole) {
            $user = $request->user();

            // ถ้า role ไม่ตรง -> logout แล้วเด้งกลับ
            if (!$user || $user->role !== $expectedRole) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => 'คุณเลือกประเภทผู้ใช้ไม่ถูกต้อง (role ไม่ตรง)'])
                    ->onlyInput('email');
            }
        }

        $request->session()->regenerate();
        return redirect()->intended(route('dashboard', absolute: false));
    }
}
