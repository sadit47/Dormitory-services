<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Parcels', description: 'Parcel management')]

#[OA\Get(
    path: '/admin/parcels',
    tags: ['Admin Parcels'],
    summary: 'List parcels',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['arrived', 'picked_up', 'cancelled'])),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Parcel list')
    ]
)]

#[OA\Get(
    path: '/admin/parcels/{parcel}',
    tags: ['Admin Parcels'],
    summary: 'Show parcel',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'parcel', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Parcel detail')
    ]
)]

#[OA\Post(
    path: '/admin/parcels',
    tags: ['Admin Parcels'],
    summary: 'Create parcel',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['tenant_id'],
                properties: [
                    new OA\Property(property: 'tenant_id', type: 'integer', example: 10),
                    new OA\Property(property: 'tracking_no', type: 'string', nullable: true, example: 'TH123456789'),
                    new OA\Property(property: 'courier', type: 'string', nullable: true, example: 'Kerry'),
                    new OA\Property(property: 'sender_name', type: 'string', nullable: true, example: 'Shopee'),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'วางไว้ที่เคาน์เตอร์'),
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
    path: '/admin/parcels/{parcel}',
    tags: ['Admin Parcels'],
    summary: 'Update parcel information',
    description: 'ใช้แก้ tracking_no, courier, sender_name, note และเพิ่มรูปเท่านั้น ถ้าจะรับพัสดุให้ใช้ /admin/parcels/{parcel}/pickup',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'parcel', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: 'tracking_no', type: 'string', nullable: true, example: 'TH123456789'),
                    new OA\Property(property: 'courier', type: 'string', nullable: true, example: 'Kerry'),
                    new OA\Property(property: 'sender_name', type: 'string', nullable: true, example: 'Shopee'),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'วางไว้ที่เคาน์เตอร์'),
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
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]

#[OA\Post(
    path: '/admin/parcels/{parcel}/pickup',
    tags: ['Admin Parcels'],
    summary: 'Mark parcel as picked up',
    description: 'ใช้ endpoint นี้เมื่อลูกค้ามารับพัสดุ ระบบจะอัปเดต status, picked_up_at และ picked_up_by_user_id ให้อัตโนมัติ',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'parcel', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Picked up'),
        new OA\Response(response: 422, description: 'Parcel already picked up')
    ]
)]

#[OA\Delete(
    path: '/admin/parcels/{parcel}',
    tags: ['Admin Parcels'],
    summary: 'Delete parcel',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'parcel', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted')
    ]
)]
final class AdminParcels {}