<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\PathItem(
 *   path="/health",
 *   @OA\Get(
 *     tags={"System"},
 *     summary="Health check",
 *     @OA\Response(
 *       response=200,
 *       description="OK",
 *       @OA\JsonContent(
 *         @OA\Property(property="ok", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="OK")
 *       )
 *     )
 *   )
 * )
 */
class HealthPath {}