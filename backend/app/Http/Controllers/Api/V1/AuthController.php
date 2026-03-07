<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Auth",
    description: "Authentication (Sanctum Bearer Token)"
)]
class AuthController extends Controller
{
    #[OA\Post(
        path: "/auth/login",
        tags: ["Auth"],
        summary: "Login and get Bearer token (Sanctum)",
        description: "Returns Sanctum token. Use it as: Authorization: Bearer {token}",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", example: "admin@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password"),
                    new OA\Property(property: "device_name", type: "string", nullable: true, example: "swagger"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "OK"),
            new OA\Response(response: 401, description: "Invalid credentials"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function login(Request $request)
    {
        \Log::info('API LOGIN HIT', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return apiResponse(null, 'Invalid credentials', 401);
        }

        $tokenName = $data['device_name'] ?? 'app';
        $token = $user->createToken($tokenName)->plainTextToken;

        return apiResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 'Login successful', 200);
    }

    #[OA\Get(
        path: "/auth/me",
        tags: ["Auth"],
        summary: "Get current user (requires Bearer token)",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "OK"),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
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
        ], 'User fetched successfully', 200);
    }

    #[OA\Post(
        path: "/auth/logout",
        tags: ["Auth"],
        summary: "Logout (revoke current token)",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "OK"),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return apiResponse(null, 'Logged out', 200);
    }
}