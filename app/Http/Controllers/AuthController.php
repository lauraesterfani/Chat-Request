<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login usando CPF e senha (para alunos).
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

        $user = auth('api')->user() ?? User::where('cpf', $request->cpf)->first();

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
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
        $token = auth('staff_admins')->attempt($credentials);

        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $user = auth('staff_admins')->user();

        // Se for primeiro acesso, força redefinição
        if ($user->must_change_password) {
            return response()->json([
                'redirect' => '/reset-password',
                'message'  => 'Você precisa redefinir sua senha antes de continuar.',
                'token'    => $token,
                'user'     => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ], 200);
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
            'expires_in' => auth('staff_admins')->factory()->getTTL() * 60,
        ]);
    }

    /**
     * Retorna dados do usuário autenticado.
     */
    public function me()
    {
        $user = auth('staff_admins')->user() ?? auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 401);
        }

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]);
    }

    /**
     * Logout.
     */
    public function logout()
    {
        if (auth('staff_admins')->check()) {
            auth('staff_admins')->logout();
        } elseif (auth('api')->check()) {
            auth('api')->logout();
        }

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    /**
     * Refresh token.
     */
    public function refresh()
    {
        if (auth('staff_admins')->check()) {
            return response()->json([
                'token' => auth('staff_admins')->refresh(),
                'expires_in' => auth('staff_admins')->factory()->getTTL() * 60,
            ]);
        } elseif (auth('api')->check()) {
            return response()->json([
                'token' => auth('api')->refresh(),
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]);
        }

        return response()->json(['message' => 'Token inválido ou expirado.'], 401);
    }

    /**
     * Redefinição de senha para staff/admin.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:8|confirmed'
        ]);

        $user = auth('staff_admins')->user() ?? auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Usuário não autenticado'], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->must_change_password = false;
        $user->save();

        return response()->json(['message' => 'Senha redefinida com sucesso']);
    }
}
