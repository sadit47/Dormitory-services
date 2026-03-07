<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Repairs', description: 'Admin repair management')]
#[OA\Get(
    path: '/admin/repairs',
    tags: ['Admin Repairs'],
    summary: 'List repairs',
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
#[OA\Patch(
    path: '/admin/repairs/{repair}/status',
    tags: ['Admin Repairs'],
    summary: 'Update repair status',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'repair', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'in_progress'),
                new OA\Property(property: 'note', type: 'string', example: 'Technician assigned'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 404, description: 'Repair not found')
    ]
)]
#[OA\Delete(
    path: '/admin/repairs/{repair}',
    tags: ['Admin Repairs'],
    summary: 'Delete repair',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'repair', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted'),
        new OA\Response(response: 404, description: 'Repair not found')
    ]
)]
final class AdminRepairs {}