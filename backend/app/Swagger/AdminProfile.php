<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Profile', description: 'Admin profile management')]
#[OA\Get(
    path: '/admin/profile',
    tags: ['Admin Profile'],
    summary: 'Get admin profile',
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
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Admin User'),
                            new OA\Property(property: 'email', type: 'string', example: 'admin@dorm.test'),
                            new OA\Property(property: 'phone', type: 'string', example: '0811111111'),
                            new OA\Property(property: 'role', type: 'string', example: 'admin'),
                        ]
                    ),
                ]
            )
        )
    ]
)]
#[OA\Put(
    path: '/admin/profile',
    tags: ['Admin Profile'],
    summary: 'Update admin profile',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Admin User'),
                new OA\Property(property: 'phone', type: 'string', example: '0811111111'),
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
final class AdminProfile {}