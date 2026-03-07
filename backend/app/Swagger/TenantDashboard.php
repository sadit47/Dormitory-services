<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tenant Dashboard', description: 'Tenant dashboard summary')]
#[OA\Get(
    path: '/tenant/dashboard/summary',
    tags: ['Tenant Dashboard'],
    summary: 'Get tenant dashboard summary',
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
                            new OA\Property(property: 'room_name', type: 'string', example: 'A-101'),
                            new OA\Property(property: 'pending_invoices', type: 'integer', example: 1),
                            new OA\Property(property: 'unpaid_amount', type: 'number', format: 'float', example: 4500),
                            new OA\Property(property: 'open_repairs', type: 'integer', example: 1),
                        ]
                    ),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden')
    ]
)]
final class TenantDashboard {}