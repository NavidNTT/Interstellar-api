<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The framework's global HandleCors middleware reads config/cors.php.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api', 'api/*') || $request->expectsJson());
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api', 'api/*')) {
                return null;
            }
            $status = $exception instanceof QueryException ? 503 : ($exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500);
            [$code, $message] = match ($status) {
                404 => ['not_found', 'The requested resource was not found.'],
                405 => ['method_not_allowed', 'This API is read-only.'],
                503 => ['service_unavailable', 'Catalog storage is temporarily unavailable.'],
                default => ['catalog_error', 'The catalog could not be loaded.'],
            };

            return response()->json(['error' => ['code' => $code, 'message' => $message]], $status, $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : []);
        });
    })->create();
