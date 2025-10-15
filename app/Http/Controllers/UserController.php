<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Exibe a lista de usuários.
     * Admins veem todos; Staff e Student têm acesso restrito.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        if ($user->isAdmin()) {
            $users = User::paginate(10);
        } elseif ($user->isStaff()) {
            $users = User::where('role', 'student')->paginate(10);
        } else {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($users);
    }

    /**
     * Cadastra um novo usuário (para admins ou cadastro público).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised()
                ],
                'cpf' => 'required|string|size:11|unique:users',
                'phone' => 'required|string|size:11',
                'birthday' => 'required|date_format:Y-m-d',
                'role' => 'nullable|in:student,staff,admin',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'cpf' => $validated['cpf'],
                'phone' => $validated['phone'],
                'birthday' => $validated['birthday'],
                'role' => $validated['role'] ?? 'student',
            ]);

            return response()->json([
                'message' => 'Usuário criado com sucesso!',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar usuário: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao criar usuário.'], 500);
        }
    }

    /**
     * Exibe os dados de um usuário específico.
     */
    public function show($id)
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        // Somente Admin pode ver qualquer usuário; staff/students apenas eles mesmos
        if (!$authUser->isAdmin() && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($user);
    }

    /**
     * Atualiza um usuário (Admin ou o próprio usuário).
     */
    public function update(Request $request, $id)
    {
        try {
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json(['message' => 'Usuário não autenticado.'], 401);
            }

            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado.'], 404);
            }

            if (!$authUser->isAdmin() && $authUser->id !== $user->id) {
                return response()->json(['message' => 'Acesso negado.'], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
                'password' => [
                    'sometimes',
                    'required',
                    'string',
                    'confirmed',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised()
                ],
                'cpf' => [
                    'sometimes',
                    'required',
                    'string',
                    'size:11',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone' => 'sometimes|required|string|size:11',
                'birthday' => 'sometimes|required|date_format:Y-m-d',
                'role' => 'sometimes|required|in:student,staff,admin',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'message' => 'Usuário atualizado com sucesso!',
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar usuário: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao atualizar usuário.'], 500);
        }
    }

    /**
     * Exclui um usuário (somente Admin).
     */
    public function destroy($id)
    {
        $authUser = Auth::user();

        if (!$authUser || !$authUser->isAdmin()) {
            return response()->json(['message' => 'Acesso negado. Somente administradores podem excluir usuários.'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Usuário excluído com sucesso.'], 200);
    }

    /**
     * Valida se o token JWT ainda é válido.
     */
    public function validateToken()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Token inválido ou expirado.'], 401);
            }

            return response()->json(['message' => 'Token válido.', 'user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao validar token.'], 500);
        }
    }
}

