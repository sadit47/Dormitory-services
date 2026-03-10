<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'ok', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Success'),
        new OA\Property(property: 'data')
    ]
)]
#[OA\Schema(
    schema: 'PaginatedResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'ok', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Success'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(type: 'object')
        ),
        new OA\Property(
            property: 'meta',
            type: 'object',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'last_page', type: 'integer', example: 3),
                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                new OA\Property(property: 'total', type: 'integer', example: 25),
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'RoomTypeMini',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Standard'),
    ]
)]
#[OA\Schema(
    schema: 'UserMini',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Somchai Jaidee'),
        new OA\Property(property: 'email', type: 'string', example: 'tenant1@dorm.test'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '0812345678'),
    ]
)]
#[OA\Schema(
    schema: 'Room',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'A101'),
        new OA\Property(property: 'floor', type: 'integer', example: 1),
        new OA\Property(property: 'room_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'price_monthly', type: 'number', format: 'float', example: 3500),
        new OA\Property(property: 'status', type: 'string', example: 'vacant'),
        new OA\Property(property: 'room_type', ref: '#/components/schemas/RoomTypeMini'),
    ]
)]
#[OA\Schema(
    schema: 'RoomMetaResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'ok', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'OK'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'room_types',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RoomTypeMini')
                ),
                new OA\Property(
                    property: 'statuses',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'value', type: 'string', example: 'vacant'),
                            new OA\Property(property: 'label', type: 'string', example: 'ว่าง'),
                        ]
                    )
                ),
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'Tenant',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'user_id', type: 'integer', example: 15),
        new OA\Property(property: 'citizen_id', type: 'string', nullable: true, example: '1234567890123'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Bangkok'),
        new OA\Property(property: 'emergency_contact', type: 'string', nullable: true, example: 'Mother 0899999999'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-03-01'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: null),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserMini'),
    ]
)]
#[OA\Schema(
    schema: 'VacantRoomMini',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'A101'),
    ]
)]
#[OA\Schema(
    schema: 'TenantMetaResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'ok', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'OK'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'vacant_rooms',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/VacantRoomMini')
                )
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'InvoiceItemInput',
    type: 'object',
    required: ['description', 'qty', 'unit_price'],
    properties: [
        new OA\Property(property: 'description', type: 'string', example: 'ค่าเช่าห้อง'),
        new OA\Property(property: 'qty', type: 'number', format: 'float', example: 1),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 3500),
    ]
)]

#[OA\Schema(
    schema: 'Repair',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 101),
        new OA\Property(property: 'tenant_id', type: 'integer', example: 10),
        new OA\Property(property: 'room_id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Air conditioner broken'),
        new OA\Property(property: 'description', type: 'string', example: 'AC not cooling properly'),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'priority', type: 'string', example: 'normal'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-03-07T10:00:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-03-07T10:30:00Z'),
    ]
)]

#[OA\Schema(
    schema: 'Payment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 301),
        new OA\Property(property: 'invoice_id', type: 'integer', example: 1001),
        new OA\Property(property: 'tenant_id', type: 'integer', example: 10),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 4500),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'payment_method', type: 'string', example: 'transfer'),
        new OA\Property(property: 'slip_url', type: 'string', example: '/api/v1/files/payment-slip.jpg'),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]

#[OA\Schema(
    schema: 'InvoiceItem',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'invoice_id', type: 'integer', example: 1001),
        new OA\Property(property: 'description', type: 'string', example: 'ค่าเช่าห้อง'),
        new OA\Property(property: 'qty', type: 'number', format: 'float', example: 1),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 3500),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 3500),
    ]
)]
#[OA\Schema(
    schema: 'RoomMini',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'A101'),
        new OA\Property(property: 'floor', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'string', example: 'occupied'),
    ]
)]
#[OA\Schema(
    schema: 'Invoice',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1001),
        new OA\Property(property: 'invoice_no', type: 'string', example: 'INV-RENT-202603-0001'),
        new OA\Property(property: 'tenant_id', type: 'integer', example: 10),
        new OA\Property(property: 'room_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'rent'),
        new OA\Property(property: 'period_month', type: 'integer', example: 3),
        new OA\Property(property: 'period_year', type: 'integer', example: 2026),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-03-10'),
        new OA\Property(property: 'status', type: 'string', example: 'unpaid'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 4000),
        new OA\Property(property: 'discount', type: 'number', format: 'float', example: 100),
        new OA\Property(property: 'total', type: 'number', format: 'float', example: 3900),
        new OA\Property(property: 'tenant', ref: '#/components/schemas/Tenant'),
        new OA\Property(property: 'room', ref: '#/components/schemas/RoomMini'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/InvoiceItem')
        ),
    ]
)]
#[OA\Schema(
    schema: 'InvoiceMetaResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'ok', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Success'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'tenants',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Tenant')
                ),
                new OA\Property(
                    property: 'rooms',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RoomMini')
                ),
            ]
        )
    ]
)]

#[OA\Schema(
    schema: 'FileMini',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'owner_user_id', type: 'integer', example: 1),
        new OA\Property(property: 'ref_type', type: 'string', example: 'announcement_image'),
        new OA\Property(property: 'ref_id', type: 'integer', example: 10),
        new OA\Property(property: 'disk', type: 'string', example: 'public'),
        new OA\Property(property: 'path', type: 'string', example: 'announcement_images/abc123.jpg'),
        new OA\Property(property: 'original_name', type: 'string', example: 'photo.jpg'),
        new OA\Property(property: 'mime', type: 'string', example: 'image/jpeg'),
        new OA\Property(property: 'size', type: 'integer', example: 204800),
    ]
)]
#[OA\Schema(
    schema: 'AnnouncementTarget',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'announcement_id', type: 'integer', example: 100),
        new OA\Property(property: 'target_type', type: 'string', example: 'all'),
        new OA\Property(property: 'target_id', type: 'integer', nullable: true, example: null),
    ]
)]
#[OA\Schema(
    schema: 'AnnouncementRead',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'announcement_id', type: 'integer', example: 100),
        new OA\Property(property: 'user_id', type: 'integer', example: 15),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true, example: '2026-03-10T09:00:00Z'),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserMini'),
    ]
)]
#[OA\Schema(
    schema: 'Announcement',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 100),
        new OA\Property(property: 'title', type: 'string', example: 'แจ้งปิดน้ำชั่วคราว'),
        new OA\Property(property: 'content', type: 'string', example: 'ขอแจ้งปิดน้ำชั่วคราวในวันพรุ่งนี้ เวลา 09:00-12:00 น.'),
        new OA\Property(property: 'type', type: 'string', example: 'maintenance'),
        new OA\Property(property: 'status', type: 'string', example: 'published'),
        new OA\Property(property: 'is_pinned', type: 'boolean', example: true),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true, example: '2026-03-10T09:00:00Z'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true, example: '2026-03-10T12:00:00Z'),
        new OA\Property(property: 'created_by_user_id', type: 'integer', example: 1),
        new OA\Property(property: 'creator', ref: '#/components/schemas/UserMini'),
        new OA\Property(
            property: 'targets',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/AnnouncementTarget')
        ),
        new OA\Property(
            property: 'reads',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/AnnouncementRead')
        ),
        new OA\Property(
            property: 'files',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/FileMini')
        ),
    ]
)]
#[OA\Schema(
    schema: 'Notification',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 15),
        new OA\Property(property: 'type', type: 'string', example: 'announcement'),
        new OA\Property(property: 'title', type: 'string', example: 'แจ้งเตือนใหม่'),
        new OA\Property(property: 'message', type: 'string', example: 'มีประกาศใหม่จากผู้ดูแลหอพัก'),
        new OA\Property(property: 'ref_type', type: 'string', nullable: true, example: 'announcement'),
        new OA\Property(property: 'ref_id', type: 'integer', nullable: true, example: 100),
        new OA\Property(property: 'is_read', type: 'boolean', example: false),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true, example: null),
    ]
)]
#[OA\Schema(
    schema: 'Parcel',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 501),
        new OA\Property(property: 'tenant_id', type: 'integer', example: 10),
        new OA\Property(property: 'room_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'tracking_no', type: 'string', nullable: true, example: 'TH123456789'),
        new OA\Property(property: 'courier', type: 'string', nullable: true, example: 'Flash Express'),
        new OA\Property(property: 'sender_name', type: 'string', nullable: true, example: 'Shopee Seller'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'วางไว้ที่เคาน์เตอร์'),
        new OA\Property(property: 'status', type: 'string', example: 'arrived'),
        new OA\Property(property: 'received_at', type: 'string', format: 'date-time', nullable: true, example: '2026-03-10T10:30:00Z'),
        new OA\Property(property: 'received_by_user_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'picked_up_at', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(property: 'picked_up_by_user_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'tenant', ref: '#/components/schemas/Tenant'),
        new OA\Property(property: 'room', ref: '#/components/schemas/RoomMini'),
        new OA\Property(property: 'receivedBy', ref: '#/components/schemas/UserMini'),
        new OA\Property(property: 'pickedUpBy', ref: '#/components/schemas/UserMini'),
        new OA\Property(
            property: 'files',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/FileMini')
        ),
    ]
)]
final class Schemas {}