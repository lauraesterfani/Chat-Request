<?php

namespace App\Http\Controllers;

use App\Models\StaffAdmin;
use App\Models\User;
use App\Services\AuditService;
use App\Services\RequestAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const STAFF_LOGIN_ACCOUNT_LIMIT = 5;

    private const STUDENT_LOGIN_ACCOUNT_LIMIT = 5;

    private const STAFF_LOGIN_DECAY_SECONDS = 60;

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
        $accountKey = 'student-login:account:'.hash_hmac('sha256', $matricula, (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($accountKey, self::STUDENT_LOGIN_ACCOUNT_LIMIT)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($accountKey));
        }

        $credentials = ['matricula' => $matricula, 'password' => $request->string('password')->toString()];
        $token = auth('api')->attempt($credentials);

        if (! $token) {
            RateLimiter::hit($accountKey, self::STAFF_LOGIN_DECAY_SECONDS);
            app(AuditService::class)->record($request, 'student_login_failed', 'auth', null, ['identifier_hash' => hash_hmac('sha256', $matricula, (string) config('app.key'))], 'failure');
            throw ValidationException::withMessages([
                'matricula' => [__('Credenciais inválidas.')],
            ]);
        }

        $user = auth('api')->user() ?? User::where('matricula', $matricula)->first();
        RateLimiter::clear($accountKey);
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

        $email = mb_strtolower(trim($request->string('email')->toString()));
        $keySecret = (string) config('app.key');
        $accountKey = 'staff-login:account:'.hash_hmac('sha256', $email, $keySecret);

        if (RateLimiter::tooManyAttempts($accountKey, self::STAFF_LOGIN_ACCOUNT_LIMIT)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($accountKey));
        }

        $credentials = ['email' => $email, 'password' => $request->string('password')->toString()];
        $token = auth('staff_admins')->attempt($credentials);

        if (! $token) {
            RateLimiter::hit($accountKey, self::STAFF_LOGIN_DECAY_SECONDS);
            app(AuditService::class)->record($request, 'staff_login_failed', 'auth', null, ['reason' => 'invalid_credentials'], 'failure');

            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $user = auth('staff_admins')->user();
        if (! $user->isActive()) {
            auth('staff_admins')->logout();
            RateLimiter::hit($accountKey, self::STAFF_LOGIN_DECAY_SECONDS);
            app(AuditService::class)->record($request, 'staff_login_failed', 'auth', null, ['reason' => 'inactive_account'], 'failure');

            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        RateLimiter::clear($accountKey);
        app(AuditService::class)->record($request, 'staff_login_succeeded', 'auth', $user->id);

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
        if ($user instanceof StaffAdmin && ! $user->isActive()) {
            return response()->json(['message' => 'Acesso não autorizado.'], 401);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'capabilities' => app(RequestAccessService::class)->capabilitiesFor($user),
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
            if (! auth('staff_admins')->user()->isActive()) {
                auth('staff_admins')->logout();

                return response()->json(['message' => 'Token inválido ou expirado.'], 401);
            }

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
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = auth('staff_admins')->user();
        if (! $user instanceof StaffAdmin || ! $user->isActive()) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }
        if (! $user->must_change_password) {
            return response()->json(['message' => 'A troca obrigatória de senha não está pendente.'], 403);
        }
        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            app(AuditService::class)->record($request, 'staff_initial_password_change_failed', 'auth', $user->id, ['reason' => 'invalid_current_password'], 'failure');

            return response()->json(['message' => 'Não foi possível redefinir a senha.'], 422);
        }

        DB::transaction(function () use ($request, $user): void {
            $user->forceFill([
                'password' => Hash::make($request->string('new_password')->toString()),
                'must_change_password' => false,
            ])->save();
            app(AuditService::class)->record($request, 'staff_initial_password_changed', 'auth', $user->id);
        });

        auth('staff_admins')->logout();

        return response()->json(['message' => 'Senha redefinida com sucesso. Faça login novamente.']);
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
