<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tenant Invoices Extra', description: 'Tenant invoice show/pdf endpoints')]
#[OA\Get(
    path: '/tenant/invoices/{invoice}',
    tags: ['Tenant Invoices Extra'],
    summary: 'Show my invoice detail',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Invoice detail'),
        new OA\Response(response: 404, description: 'Invoice not found')
    ]
)]
#[OA\Get(
    path: '/tenant/invoices/{invoice}/pdf',
    tags: ['Tenant Invoices Extra'],
    summary: 'Download my invoice PDF',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'PDF file',
            content: new OA\MediaType(mediaType: 'application/pdf')
        ),
        new OA\Response(response: 404, description: 'Invoice not found')
    ]
)]
final class TenantInvoicesExtra {}