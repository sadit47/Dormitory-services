<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tenant Profile', description: 'Tenant profile management')]
#[OA\Get(
    path: '/tenant/profile',
    tags: ['Tenant Profile'],
    summary: 'Get tenant profile',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Profile detail',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Success'),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 10),
                            new OA\Property(property: 'name', type: 'string', example: 'Somchai Jaidee'),
                            new OA\Property(property: 'email', type: 'string', example: 'tenant1@dorm.test'),
                            new OA\Property(property: 'phone', type: 'string', example: '0812345678'),
                            new OA\Property(property: 'role', type: 'string', example: 'tenant'),
                            new OA\Property(property: 'room_id', type: 'integer', example: 1),
                        ]
                    ),
                ]
            )
        )
    ]
)]
#[OA\Put(
    path: '/tenant/profile',
    tags: ['Tenant Profile'],
    summary: 'Update tenant profile',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Somchai Jaidee'),
                new OA\Property(property: 'phone', type: 'string', example: '0812345678'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'new-password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'new-password'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]
final class TenantProfile {}