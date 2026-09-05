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

        if (!$user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return $next($request);
    }
}
