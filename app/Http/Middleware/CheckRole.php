<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Tenta encontrar o usuário em qualquer um dos guards ativos
        $user = Auth::guard('staff_admins')->user() ?: Auth::guard('api')->user();

        // 1. Verifica se o usuário está logado em algum lugar
        if (!$user) {
            return response()->json([
                'message' => 'Não autenticado.',
                'debug' => 'Nenhum usuário detectado nos guards staff_admins ou api.'
            ], 401);
        }

        // 2. Verifica se o papel (role) do usuário está na lista permitida
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Acesso negado.',
                'perfil_atual' => $user->role,
                'perfis_necessarios' => $roles
            ], 403);
        }

        return $next($request);
    }
}