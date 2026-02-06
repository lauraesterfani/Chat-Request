<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // 1. Verifica autenticação
        if (! $request->user()) {
            return response()->json([
                'debug_error' => 'Usuario nao encontrado no request',
                'message' => 'Não autenticado.'
            ], 401);
        }

        // 2. Prepara papéis
        $requiredRoles = explode(',', $roles);
        $userRole = $request->user()->role; 

        // 3. Verifica permissão
        if (! in_array($userRole, $requiredRoles)) {
            // AQUI ESTÁ O SEGREDO: Vamos mostrar no erro quem você é!
            return response()->json([
                'message' => 'Acesso negado.',
                'debug_info' => [
                    'seu_papel_no_banco' => $userRole,
                    'papeis_exigidos_pela_rota' => $requiredRoles,
                    'id_do_usuario' => $request->user()->id,
                    'nome_do_usuario' => $request->user()->name
                ]
            ], 403);
        }

        return $next($request);
    }
}