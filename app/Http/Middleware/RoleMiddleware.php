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
                'message' => 'Não autenticado.',
            ], 401);
        }

        // 2. Prepara papéis
        $requiredRoles = explode(',', $roles);
        $userRole = $request->user()->role;

        // 3. Verifica permissão
        if (! in_array($userRole, $requiredRoles)) {
            return response()->json([
                'message' => 'Acesso negado.',
            ], 403);
        }

        return $next($request);
    }
}
