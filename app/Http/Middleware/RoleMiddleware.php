<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Lida com a requisição, verificando se o usuário autenticado possui
     * um dos papéis necessários.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles  Papéis permitidos (separados por vírgula, ex: 'admin,staff')
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // 1. Verificar se o usuário está autenticado
        if (! $request->user()) {
            // Retorna não autorizado se não houver token Sanctum válido
            return response()->json(['message' => 'Não autenticado. Token inválido ou ausente.'], 401);
        }

        // 2. Obter a lista de papéis necessários
        // Ex: $roles = 'admin,staff' se torna ['admin', 'staff']
        $requiredRoles = explode(',', $roles);
        $userRole = $request->user()->role;

        // 3. Verificar se o papel do usuário está na lista de papéis necessários
        if (! in_array($userRole, $requiredRoles)) {
            // Se o papel do usuário não for um dos papéis permitidos
            return response()->json(['message' => 'Acesso negado. Você não tem a permissão necessária (' . $userRole . ').'], 403);
        }

        // Se o usuário estiver autenticado e tiver o papel correto, prosseguir
        return $next($request);
    }
}
