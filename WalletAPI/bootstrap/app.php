<?php

use App\Models\Wallet;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        //
    })

    ->withExceptions(function (Exceptions $exceptions) {

        // 401
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.'
            ], 401);
        });

        // 404 Wallet
        $exceptions->render(function (NotFoundHttpException $e, $request) {

            if ($request->is('api/wallets/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet introuvable.'
                ], 404);
            }

        });

    })->create();
