<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ── Alias middleware agar bisa dipakai di routes ───────
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtAuthenticate::class,
            'role.admin' => \App\Http\Middleware\IsAdmin::class,
            'role.user'  => \App\Http\Middleware\IsUser::class,
            'log.api'    => \App\Http\Middleware\LogApiRequest::class,
        ]);

        // ── Terapkan LogApiRequest ke semua API route ──────────
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\LogApiRequest::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ── Tangani exception secara global ───────────────────
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan.',
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'HTTP method tidak diizinkan.',
                ], 405);
            }
        });

    })
    ->create();
