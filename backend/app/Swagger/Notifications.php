<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notifications', description: 'User notifications')]
#[OA\Get(
    path: '/notifications',
    tags: ['Notifications'],
    summary: 'List notifications',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Notification list')
    ]
)]
#[OA\Post(
    path: '/notifications/{notification}/read',
    tags: ['Notifications'],
    summary: 'Mark one notification as read',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 403, description: 'Forbidden')
    ]
)]
#[OA\Post(
    path: '/notifications/read-all',
    tags: ['Notifications'],
    summary: 'Mark all notifications as read',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Updated')
    ]
)]
final class Notifications {}