<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tenant Announcements', description: 'Tenant announcement access')]
#[OA\Get(
    path: '/tenant/announcements',
    tags: ['Tenant Announcements'],
    summary: 'List announcements visible to tenant',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['general', 'urgent', 'maintenance'])),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Announcement list'),
        new OA\Response(response: 403, description: 'Forbidden')
    ]
)]
#[OA\Get(
    path: '/tenant/announcements/{announcement}',
    tags: ['Tenant Announcements'],
    summary: 'Show announcement detail for tenant',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'announcement', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Announcement detail'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Announcement not found')
    ]
)]
#[OA\Post(
    path: '/tenant/announcements/{announcement}/read',
    tags: ['Tenant Announcements'],
    summary: 'Mark announcement as read',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'announcement', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 403, description: 'Forbidden')
    ]
)]
#[OA\Get(
    path: '/tenant/announcements/urgent/active',
    tags: ['Tenant Announcements'],
    summary: 'List active urgent announcements',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Urgent announcements')
    ]
)]
final class TenantAnnouncements {}