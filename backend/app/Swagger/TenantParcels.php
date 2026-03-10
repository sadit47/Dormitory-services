<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tenant Parcels', description: 'Tenant parcel access')]
#[OA\Get(
    path: '/tenant/parcels',
    tags: ['Tenant Parcels'],
    summary: 'List tenant parcels',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['arrived', 'picked_up', 'cancelled'])),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Parcel list')
    ]
)]
#[OA\Get(
    path: '/tenant/parcels/{parcel}',
    tags: ['Tenant Parcels'],
    summary: 'Show tenant parcel detail',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'parcel', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
    ],
    responses: [
        new OA\Response(response: 200, description: 'Parcel detail'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Parcel not found')
    ]
)]
final class TenantParcels {}