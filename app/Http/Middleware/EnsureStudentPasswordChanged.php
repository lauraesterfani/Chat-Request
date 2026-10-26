<?php

namespace App\Http\Middleware;

use App\Models\StaffAdmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = auth('staff_admins')->user();
        if ($staff instanceof StaffAdmin && ! $staff->isActive()) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

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
