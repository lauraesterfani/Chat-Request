<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login usando matrícula e senha (para alunos).
     */
    public function login(Request $request)
    {
        $request->validate([
            'matricula' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string'],
        ]);

        $matricula = strtoupper(preg_replace('/\s+/', '', $request->string('matricula')->toString()));
        $credentials = ['matricula' => $matricula, 'password' => $request->string('password')->toString()];
        $token = auth('api')->attempt($credentials);

        if (! $token) {
            app(AuditService::class)->record($request, 'student_login_failed', 'auth', null, ['identifier_hash' => hash('sha256', $matricula)]);
            throw ValidationException::withMessages([
                'matricula' => [__('Credenciais inválidas.')],
            ]);
        }

        $user = auth('api')->user() ?? User::where('matricula', $matricula)->first();
        app(AuditService::class)->record($request, 'student_login_succeeded', 'auth', $user->id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'message' => 'Login bem-sucedido.',
            'must_change_password' => (bool) $user->must_change_password,
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

        if (! $token) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $user = auth('staff_admins')->user();

        // Se for primeiro acesso, força redefinição
        if ($user->must_change_password) {
            return response()->json([
                'redirect' => '/reset-password',
                'message' => 'Você precisa redefinir sua senha antes de continuar.',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ], 200);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
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

        if (! $user) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 401);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'must_change_password' => (bool) $user->must_change_password,
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
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = auth('staff_admins')->user() ?? auth('api')->user();

        if (! $user) {
            return response()->json(['message' => 'Usuário não autenticado'], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->must_change_password = false;
        $user->save();

        return response()->json(['message' => 'Senha redefinida com sucesso']);
    }

    public function changeInitialPassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = auth('api')->user();
        if (! $user || ! $user->isStudent()) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        if (! $user->must_change_password) {
            return response()->json(['message' => 'A troca obrigatória de senha não está pendente.'], 422);
        }

        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            return response()->json(['message' => 'Não foi possível alterar a senha.'], 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->string('new_password')->toString()),
            'must_change_password' => false,
        ])->save();
        app(AuditService::class)->record($request, 'student_initial_password_changed', 'auth', $user->id);

        auth('api')->logout();
        $renewedToken = auth('api')->login($user);

        return response()->json([
            'message' => 'Senha alterada com sucesso.',
            'must_change_password' => false,
            'token' => $renewedToken,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
