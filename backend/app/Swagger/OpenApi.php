<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="Dorm Service API",
 *     version="1.0.0",
 *     description="API documentation for Dorm Service (Laravel + React)"
 *   ),
 *   @OA\Server(
 *     url="/api/v1",
 *     description="API v1"
 *   ),
 *   @OA\Components(
 *     @OA\SecurityScheme(
 *       securityScheme="sanctum",
 *       type="apiKey",
 *       in="header",
 *       name="Authorization",
 *       description="Bearer <token>"
 *     )
 *   )
 * )
 */
class OpenApi {}