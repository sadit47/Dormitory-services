<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tenant Payments', description: 'Tenant payment upload')]
#[OA\Post(
    path: '/tenant/payments/{invoice}',
    tags: ['Tenant Payments'],
    summary: 'Upload payment for invoice',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['amount', 'paid_at', 'slip'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 4500),
                    new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: '2026-03-06 14:20:00'),
                    new OA\Property(property: 'note', type: 'string', example: 'Paid via mobile banking'),
                    new OA\Property(property: 'slip', type: 'string', format: 'binary'),
                ],
                type: 'object'
            )
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Payment uploaded'),
        new OA\Response(response: 422, description: 'Validation error'),
        new OA\Response(response: 404, description: 'Invoice not found')
    ]
)]
final class TenantPayments {}