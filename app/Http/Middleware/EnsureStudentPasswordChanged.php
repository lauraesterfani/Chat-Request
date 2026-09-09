<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = auth('api')->user();

        if ($student && $student->isStudent() && $student->must_change_password) {
            return response()->json([
                'message' => 'A troca de senha é obrigatória antes de continuar.',
                'must_change_password' => true,
            ], 403);
        }

        return $next($request);
    }
}
