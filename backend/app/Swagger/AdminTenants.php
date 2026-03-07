<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Tenants', description: 'Admin tenant management')]
#[OA\Get(
    path: '/admin/tenants/meta',
    tags: ['Admin Tenants'],
    summary: 'Get tenant meta',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Tenant meta',
            content: new OA\JsonContent(ref: '#/components/schemas/TenantMetaResponse')
        )
    ]
)]
#[OA\Get(
    path: '/admin/tenants',
    tags: ['Admin Tenants'],
    summary: 'List tenants',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Tenant list')
    ]
)]
#[OA\Get(
    path: '/admin/tenants/{tenant}',
    tags: ['Admin Tenants'],
    summary: 'Show tenant detail',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Tenant detail'),
        new OA\Response(response: 404, description: 'Tenant not found')
    ]
)]
#[OA\Post(
    path: '/admin/tenants',
    tags: ['Admin Tenants'],
    summary: 'Create tenant',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 120, example: 'Somchai Jaidee'),
                new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 190, example: 'tenant1@dorm.test'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password123'),
                new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 50, example: '0812345678'),
                new OA\Property(property: 'citizen_id', type: 'string', nullable: true, maxLength: 50, example: '1234567890123'),
                new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Bangkok'),
                new OA\Property(property: 'emergency_contact', type: 'string', nullable: true, maxLength: 190, example: 'Mother 0899999999'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-03-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                new OA\Property(property: 'room_id', type: 'integer', nullable: true, example: 1),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]
#[OA\Put(
    path: '/admin/tenants/{tenant}',
    tags: ['Admin Tenants'],
    summary: 'Update tenant',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 120, example: 'Somchai Jaidee'),
                new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 190, example: 'tenant1@dorm.test'),
                new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 50, example: '0812345678'),
                new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true, minLength: 8, example: 'newpassword123'),
                new OA\Property(property: 'citizen_id', type: 'string', nullable: true, maxLength: 50, example: '1234567890123'),
                new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Bangkok'),
                new OA\Property(property: 'emergency_contact', type: 'string', nullable: true, maxLength: 190, example: 'Mother 0899999999'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-03-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                new OA\Property(property: 'room_id', type: 'integer', nullable: true, example: 2),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]
#[OA\Delete(
    path: '/admin/tenants/{tenant}',
    tags: ['Admin Tenants'],
    summary: 'Delete tenant',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted'),
        new OA\Response(response: 422, description: 'Cannot delete tenant')
    ]
)]
final class AdminTenants {}