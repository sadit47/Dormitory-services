<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Dorm Service API",
    version: "1.0.0",
    description: "API documentation for Dorm Service"
)]
#[OA\Server(
    url: "/api/v1",
    description: "API v1"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Sanctum",
    description: "Paste token only (Swagger will send: Authorization: Bearer <token>)"
)]
final class ApiSpec {}