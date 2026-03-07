<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Invoices', description: 'Admin invoice management')]
#[OA\Get(
    path: '/admin/invoices',
    tags: ['Admin Invoices'],
    summary: 'List invoices',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['rent', 'utility', 'repair', 'cleaning'])),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Invoice list')
    ]
)]
#[OA\Get(
    path: '/admin/invoices/meta',
    tags: ['Admin Invoices'],
    summary: 'Get invoice meta',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Invoice meta',
            content: new OA\JsonContent(ref: '#/components/schemas/InvoiceMetaResponse')
        )
    ]
)]
#[OA\Get(
    path: '/admin/invoices/{invoice}',
    tags: ['Admin Invoices'],
    summary: 'Show invoice detail',
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
    path: '/admin/invoices/{invoice}/pdf',
    tags: ['Admin Invoices'],
    summary: 'Preview invoice PDF',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'download', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', example: true)),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'PDF stream',
            content: new OA\MediaType(mediaType: 'application/pdf')
        )
    ]
)]
#[OA\Post(
    path: '/admin/invoices',
    tags: ['Admin Invoices'],
    summary: 'Create invoice',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['tenant_id', 'type', 'period_month', 'period_year', 'items'],
            properties: [
                new OA\Property(property: 'tenant_id', type: 'integer', example: 10),
                new OA\Property(property: 'room_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'type', type: 'string', enum: ['rent', 'utility', 'repair', 'cleaning'], example: 'rent'),
                new OA\Property(property: 'period_month', type: 'integer', minimum: 1, maximum: 12, example: 3),
                new OA\Property(property: 'period_year', type: 'integer', minimum: 2000, maximum: 2100, example: 2026),
                new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-03-10'),
                new OA\Property(property: 'discount', type: 'number', format: 'float', nullable: true, minimum: 0, example: 100),
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    minItems: 1,
                    items: new OA\Items(ref: '#/components/schemas/InvoiceItemInput')
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]
#[OA\Put(
    path: '/admin/invoices/{invoice}',
    tags: ['Admin Invoices'],
    summary: 'Update invoice',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['items'],
            properties: [
                new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-03-15'),
                new OA\Property(property: 'discount', type: 'number', format: 'float', nullable: true, minimum: 0, example: 100),
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    minItems: 1,
                    items: new OA\Items(ref: '#/components/schemas/InvoiceItemInput')
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 409, description: 'Paid invoice cannot be edited'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]
#[OA\Delete(
    path: '/admin/invoices/{invoice}',
    tags: ['Admin Invoices'],
    summary: 'Delete invoice',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted'),
        new OA\Response(response: 409, description: 'Paid invoice cannot be deleted')
    ]
)]
final class AdminInvoices {}