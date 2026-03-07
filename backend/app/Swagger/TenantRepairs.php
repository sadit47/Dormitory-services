<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tenant Repairs', description: 'Tenant repair requests')]
#[OA\Get(
    path: '/tenant/repairs',
    tags: ['Tenant Repairs'],
    summary: 'List my repairs',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Repair list',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Success'),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Repair')
                    )
                ]
            )
        )
    ]
)]
#[OA\Post(
    path: '/tenant/repairs',
    tags: ['Tenant Repairs'],
    summary: 'Create repair request',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title', 'description'],
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Air conditioner leaking'),
                new OA\Property(property: 'description', type: 'string', example: 'Water dripping from indoor unit'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]
final class TenantRepairs {}