<?php

declare(strict_types=1);

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'VoyagerRent API', description: 'Versioned API for the VoyagerRent multi-tenant rental marketplace.')]
#[OA\Server(url: '/api/v1', description: 'Current platform server')]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', scheme: 'bearer', bearerFormat: 'JWT')]
#[OA\Tag(name: 'Authentication')]
#[OA\Tag(name: 'Marketplace')]
#[OA\Tag(name: 'Reservations')]
#[OA\PathItem(
    path: '/auth/mobile/login',
    post: new OA\Post(
        tags: ['Authentication'],
        operationId: 'mobileLogin',
        summary: 'Create a mobile JWT session and register a device',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['email', 'password', 'device_id', 'platform', 'app_version'], properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'password', type: 'string', format: 'password'),
            new OA\Property(property: 'device_id', type: 'string'),
            new OA\Property(property: 'platform', type: 'string', enum: ['android', 'ios']),
            new OA\Property(property: 'app_version', type: 'string'),
            new OA\Property(property: 'push_token', type: 'string', nullable: true),
        ])),
        responses: [new OA\Response(response: 200, description: 'Authenticated mobile session'), new OA\Response(response: 422, description: 'Invalid credentials or request')],
    ),
)]
#[OA\PathItem(
    path: '/marketplace/search',
    get: new OA\Get(
        tags: ['Marketplace'],
        operationId: 'searchMarketplaceOffers',
        summary: 'Search public rental offers with live capacity and price',
        parameters: [
            new OA\Parameter(name: 'pickup_at', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'return_at', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'pickup_branch_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'return_branch_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated comparable offers')],
    ),
)]
#[OA\PathItem(
    path: '/reservations',
    post: new OA\Post(
        tags: ['Reservations'],
        operationId: 'createReservationHold',
        summary: 'Create a time-limited reservation and inventory hold',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'Idempotency-Key', in: 'header', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['vehicle_group_id', 'rate_plan_id', 'pickup_branch_id', 'return_branch_id', 'pickup_at', 'return_at'], properties: [
            new OA\Property(property: 'vehicle_group_id', type: 'integer'), new OA\Property(property: 'rate_plan_id', type: 'integer'),
            new OA\Property(property: 'pickup_branch_id', type: 'integer'), new OA\Property(property: 'return_branch_id', type: 'integer'),
            new OA\Property(property: 'pickup_at', type: 'string', format: 'date-time'), new OA\Property(property: 'return_at', type: 'string', format: 'date-time'),
        ])),
        responses: [new OA\Response(response: 201, description: 'Reservation hold created'), new OA\Response(response: 422, description: 'Invalid or unavailable request')],
    ),
)]
final class OpenApi {}
