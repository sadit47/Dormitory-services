<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Announcements', description: 'Admin announcement management')]

#[OA\Get(
    path: '/admin/announcements',
    tags: ['Admin Announcements'],
    summary: 'List announcements',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft','published','expired'])),
        new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['general','urgent','maintenance'])),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', example: 10)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Announcement list')
    ]
)]

#[OA\Get(
    path: '/admin/announcements/{announcement}',
    tags: ['Admin Announcements'],
    summary: 'Show announcement',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'announcement', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Announcement detail')
    ]
)]

#[OA\Post(
    path: '/admin/announcements',
    tags: ['Admin Announcements'],
    summary: 'Create announcement',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['title','content','type'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'type', type: 'string', enum: ['general','urgent','maintenance']),
                    new OA\Property(property: 'status', type: 'string', enum: ['draft','published','expired']),
                    new OA\Property(property: 'is_pinned', type: 'boolean'),
                    new OA\Property(property: 'starts_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'ends_at', type: 'string', format: 'date-time'),
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created')
    ]
)]

#[OA\Put(
    path: '/admin/announcements/{announcement}',
    tags: ['Admin Announcements'],
    summary: 'Update announcement',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'announcement', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'is_pinned', type: 'boolean'),
                    new OA\Property(property: 'starts_at', type: 'string'),
                    new OA\Property(property: 'ends_at', type: 'string'),
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated')
    ]
)]

#[OA\Post(
    path: '/admin/announcements/{announcement}/publish',
    tags: ['Admin Announcements'],
    summary: 'Publish announcement',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'announcement', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Published')
    ]
)]

#[OA\Post(
    path: '/admin/announcements/{announcement}/expire',
    tags: ['Admin Announcements'],
    summary: 'Expire announcement',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'announcement', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Expired')
    ]
)]

#[OA\Delete(
    path: '/admin/announcements/{announcement}',
    tags: ['Admin Announcements'],
    summary: 'Delete announcement',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'announcement', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted')
    ]
)]

final class AdminAnnouncements {}