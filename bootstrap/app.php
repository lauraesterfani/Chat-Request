<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registre seu middleware aqui usando um alias
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);

        // Adiciona ForceJsonResponse ao grupo de middleware da API
        $middleware->prependToGroup('api', [ForceJsonResponse::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
