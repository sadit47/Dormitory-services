<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Dashboard', description: 'Admin dashboard summary')]
#[OA\Get(
    path: '/admin/dashboard/summary',
    tags: ['Admin Dashboard'],
    summary: 'Get admin dashboard summary',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Dashboard summary',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Success'),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'total_rooms', type: 'integer', example: 50),
                            new OA\Property(property: 'occupied_rooms', type: 'integer', example: 42),
                            new OA\Property(property: 'available_rooms', type: 'integer', example: 8),
                            new OA\Property(property: 'total_tenants', type: 'integer', example: 42),
                            new OA\Property(property: 'pending_invoices', type: 'integer', example: 7),
                            new OA\Property(property: 'pending_repairs', type: 'integer', example: 3),
                            new OA\Property(property: 'pending_payments', type: 'integer', example: 5),
                        ]
                    ),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden')
    ]
)]
final class AdminDashboard {}