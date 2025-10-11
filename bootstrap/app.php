<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\RoleMiddleware; // Importa a classe do nosso Middleware
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
        // Prepend (adiciona no início) para rotas da API
        $middleware->prependToGroup('api', [ForceJsonResponse::class]);

        // Registro do alias 'role' para uso nas rotas
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
