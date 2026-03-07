<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tenant Invoices', description: 'Tenant invoice access')]
#[OA\Get(
    path: '/tenant/invoices',
    tags: ['Tenant Invoices'],
    summary: 'List my invoices',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Invoice list',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Success'),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Invoice')
                    )
                ]
            )
        )
    ]
)]
final class TenantInvoices {}