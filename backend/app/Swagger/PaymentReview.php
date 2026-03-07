<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Payment Review', description: 'Admin review tenant payments')]
#[OA\Get(
    path: '/admin/payments/pending',
    tags: ['Payment Review'],
    summary: 'List pending payments',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Pending payment list',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Success'),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 301),
                                new OA\Property(property: 'invoice_id', type: 'integer', example: 1001),
                                new OA\Property(property: 'tenant_name', type: 'string', example: 'Somchai Jaidee'),
                                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 4500),
                                new OA\Property(property: 'status', type: 'string', example: 'pending'),
                                new OA\Property(property: 'slip_url', type: 'string', example: '/api/v1/files/payment-slip.jpg'),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        )
    ]
)]
#[OA\Post(
    path: '/admin/payments/{payment}/approve',
    tags: ['Payment Review'],
    summary: 'Approve payment',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'payment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Approved'),
        new OA\Response(response: 404, description: 'Payment not found')
    ]
)]
#[OA\Post(
    path: '/admin/payments/{payment}/reject',
    tags: ['Payment Review'],
    summary: 'Reject payment',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'payment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Slip is unclear')
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Rejected'),
        new OA\Response(response: 404, description: 'Payment not found')
    ]
)]
final class PaymentReview {}