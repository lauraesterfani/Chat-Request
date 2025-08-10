<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário está autenticado e se o role_id é de administrador (id 1)
        if (Auth::check() && Auth::user()->role_id == 1) {
            return $next($request);
        }

        // Se o usuário não for um admin, retorna uma resposta de acesso não autorizado
        return response()->json(['message' => 'Acesso não autorizado. Você não tem permissão de administrador.'], 403);
    }
}
