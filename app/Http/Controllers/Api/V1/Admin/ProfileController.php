<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'data' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required','string','max:255'],
            'phone' => ['nullable','string','max:50'],
        ]);

        $request->user()->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        return response()->json([
            'message' => 'Updated',
        ]);
    }
}
