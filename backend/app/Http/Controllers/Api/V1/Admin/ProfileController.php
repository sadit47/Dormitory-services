<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $u = $request->user();

        return apiResponse([
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
        ], 'Profile fetched');
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['required','string','max:255'],
            'phone' => ['nullable','string','max:50'],
        ]);

        $request->user()->update([
            'name'  => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        return apiResponse(null, 'Profile updated');
    }
}
