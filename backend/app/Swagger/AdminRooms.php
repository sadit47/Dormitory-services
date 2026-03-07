<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Rooms', description: 'Admin room management')]
#[OA\Get(
    path: '/admin/rooms/meta',
    tags: ['Admin Rooms'],
    summary: 'Get room meta',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Room meta',
            content: new OA\JsonContent(ref: '#/components/schemas/RoomMetaResponse')
        )
    ]
)]
#[OA\Get(
    path: '/admin/rooms',
    tags: ['Admin Rooms'],
    summary: 'List rooms',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['vacant', 'occupied', 'maintenance'])),
        new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Room list')
    ]
)]
#[OA\Get(
    path: '/admin/rooms/{room}',
    tags: ['Admin Rooms'],
    summary: 'Show room detail',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'room', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Room detail',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ok', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Success'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Room'),
                ]
            )
        ),
        new OA\Response(response: 404, description: 'Room not found')
    ]
)]
#[OA\Post(
    path: '/admin/rooms',
    tags: ['Admin Rooms'],
    summary: 'Create room',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['code', 'floor', 'room_type_id', 'price_monthly', 'status'],
            properties: [
                new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'A101'),
                new OA\Property(property: 'floor', type: 'integer', minimum: 0, example: 1),
                new OA\Property(property: 'room_type_id', type: 'integer', example: 1),
                new OA\Property(property: 'price_monthly', type: 'number', format: 'float', minimum: 0, example: 3500),
                new OA\Property(property: 'status', type: 'string', enum: ['vacant', 'occupied', 'maintenance'], example: 'vacant'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]
#[OA\Put(
    path: '/admin/rooms/{room}',
    tags: ['Admin Rooms'],
    summary: 'Update room',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'room', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'A101'),
                new OA\Property(property: 'floor', type: 'integer', minimum: 0, example: 2),
                new OA\Property(property: 'room_type_id', type: 'integer', example: 1),
                new OA\Property(property: 'price_monthly', type: 'number', format: 'float', minimum: 0, example: 3800),
                new OA\Property(property: 'status', type: 'string', enum: ['vacant', 'occupied', 'maintenance'], example: 'occupied'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]
#[OA\Delete(
    path: '/admin/rooms/{room}',
    tags: ['Admin Rooms'],
    summary: 'Delete room',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'room', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted')
    ]
)]
#[OA\Get(
    path: '/admin/rooms/{room}/tenant',
    tags: ['Admin Rooms'],
    summary: 'Get active tenant of room',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'room', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Tenant in room')
    ]
)]
final class AdminRooms {}