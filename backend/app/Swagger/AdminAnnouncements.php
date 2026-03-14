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
        new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'expired'])),
        new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['general', 'urgent', 'maintenance'])),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
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
                required: ['title', 'content', 'type'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 190, example: 'แจ้งปิดน้ำชั่วคราว'),
                    new OA\Property(property: 'content', type: 'string', example: 'ขอแจ้งปิดน้ำชั่วคราวในวันพรุ่งนี้'),
                    new OA\Property(property: 'type', type: 'string', enum: ['general', 'urgent', 'maintenance'], example: 'maintenance'),
                    new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'expired'], nullable: true, example: 'draft'),
                    new OA\Property(property: 'is_pinned', type: 'boolean', nullable: true, example: true),
                    new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true, example: '2026-03-10 09:00:00'),
                    new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true, example: '2026-03-10 12:00:00'),
                    new OA\Property(
                        property: 'images[]',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'binary')
                    ),
                ],
                type: 'object'
            )
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]

#[OA\Post(
    path: '/admin/announcements/{announcement}',
    tags: ['Admin Announcements'],
    summary: 'Update announcement',
    description: 'ใช้แก้ title, content, type, status, is_pinned, starts_at, ends_at และเพิ่มรูปเท่านั้น ส่วน publish/expire ใช้ action แยก',
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
                    new OA\Property(property: 'title', type: 'string', maxLength: 190, example: 'อัปเดตหัวข้อ'),
                    new OA\Property(property: 'content', type: 'string', example: 'อัปเดตเนื้อหา'),
                    new OA\Property(property: 'type', type: 'string', enum: ['general', 'urgent', 'maintenance'], nullable: true, example: 'general'),
                    new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'expired'], nullable: true, example: 'published'),
                    new OA\Property(property: 'is_pinned', type: 'boolean', nullable: true, example: false),
                    new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true, example: '2026-03-10 09:00:00'),
                    new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true, example: '2026-03-11 18:00:00'),
                ],
                type: 'object'
            )
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 422, description: 'Validation error')
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