<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        \Log::info('API LOGIN HIT', [
            'email' => request('email'),
            'ip' => request()->ip(),
        ]);

        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
            'device_name' => ['nullable','string','max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return apiResponse(null, 'Invalid credentials', 401);
        }

        $tokenName = $data['device_name'] ?? 'app';
        $token = $user->createToken($tokenName)->plainTextToken;

        return apiResponse([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 'Login successful');
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return apiResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 'User fetched successfully');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return apiResponse(null, 'Logged out');
    }
}

