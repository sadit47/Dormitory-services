<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class HealthController extends Controller
{
    #[OA\Get(
        path: '/health',
        operationId: 'healthCheck',
        tags: ['System'],
        summary: 'Health check',
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'OK'),
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'message' => 'OK',
        ]);
    }
}