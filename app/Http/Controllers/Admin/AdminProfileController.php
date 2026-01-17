<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile.edit');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $request->user()->update([
            'name'  => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        return back()->with('success', 'อัปเดตข้อมูลเรียบร้อย');
    }
}
