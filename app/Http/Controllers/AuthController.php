<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Login usando CPF e senha (para alunos e admins).
     */
    public function login(Request $request)
    {
        $request->validate([
            'cpf' => ['required', 'string', 'max:11'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('cpf', 'password');

        $token = auth('api')->attempt($credentials);

        if (!$token) {
            throw ValidationException::withMessages([
                'cpf' => [__('Credenciais inválidas. Verifique o CPF e a senha.')],
            ]);
        }

        $user = auth('api')->user();

        if (!$user) {
            $user = User::where('cpf', $request->cpf)->first();
        }

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role, // 🔑 importante para o frontend
            ],
            'token' => $token,
            'message' => 'Login bem-sucedido.',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 200);
    }

    /**
     * Login administrativo (staff/admin via email).
     */
    public function loginStaff(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('email', 'password');

        $token = auth('api')->attempt($credentials);

        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $user = auth('api')->user();

        // Bloqueia aluno
        if ($user->role === 'student') {
            return response()->json([
                'message' => 'Acesso negado. Apenas staff ou admin.'
            ], 403);
        }

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
            'token' => $token,
            'message' => 'Login administrativo realizado com sucesso.',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    /**
     * Retorna dados do usuário autenticado.
     */
    public function me()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 401);
        }

        return response()->json($user);
    }

    /**
     * Logout.
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    /**
     * Refresh token.
     */
    public function refresh()
    {
        return response()->json([
            'token' => auth('api')->refresh(),
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
