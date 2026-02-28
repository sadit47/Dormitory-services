<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'tenant.profile' => \App\Http\Middleware\EnsureTenantProfile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {

        if ($request->is('api/*') || $request->expectsJson()) {

            // Validation 422
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return apiResponse(
                    $e->errors(),
                    'Validation failed',
                    422
                );
            }

            // Unauthenticated 401
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return apiResponse(null, 'Unauthenticated', 401);
            }

            // Forbidden 403
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException
                && $e->getStatusCode() === 403) {
                return apiResponse(null, 'Forbidden', 403);
            }

            // Not found 404
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return apiResponse(null, 'Not Found', 404);
            }

            // Default 500
            return apiResponse(
                null,
                config('app.debug') ? $e->getMessage() : 'Server Error',
                500
            );
        }

        return null; // ให้ web (Blade) ใช้ default
    });
    })->create();
