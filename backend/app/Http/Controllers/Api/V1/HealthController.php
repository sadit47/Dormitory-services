<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class HealthController extends Controller
{
    /**
     * @OA\Get(
     *   path="/health",
     *   tags={"System"},
     *   summary="Health check",
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       @OA\Property(property="ok", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="OK")
     *     )
     *   )
     * )
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(['ok' => true, 'message' => 'OK']);
    }
}