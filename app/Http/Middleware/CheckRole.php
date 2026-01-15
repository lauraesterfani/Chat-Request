<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * O terceiro parâmetro (...$roles) transforma os argumentos da rota em um array.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Verifica se o usuário está logado
        if (!$request->user()) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        // 2. Verifica se o papel (role) do usuário está na lista permitida
        if (!in_array($request->user()->role, $roles)) {
            return response()->json([
                'message' => 'Acesso negado. Seu perfil não tem permissão para esta área.'
            ], 403);
        }

        return $next($request);
    }
}