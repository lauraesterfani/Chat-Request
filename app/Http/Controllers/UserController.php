<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * =====================================================
     * CADASTRO DE ALUNO (PÚBLICO)
     * Rota: POST /api/register
     * Role SEMPRE será student
     * =====================================================
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->filled('matricula')) {
            $request->merge(['matricula' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('matricula')))]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'cpf' => 'required|string|size:11|unique:users,cpf',
            'phone' => 'required|string|max:20',
            'matricula' => ['required', 'string', 'regex:/^\\d{4}\\d[A-Z]{2,10}IG\\d{3,6}$/i', 'max:50', 'unique:users,matricula'],
            'course_id' => 'required|uuid|exists:courses,id',
            'birthday' => 'required|date_format:Y-m-d',
        ]);

        try {
            $user = null;

            DB::transaction(function () use ($validated, &$user) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'cpf' => $validated['cpf'],
                    'phone' => $validated['phone'],
                    'matricula' => $validated['matricula'],
                    'course_id' => $validated['course_id'],
                    'birthday' => $validated['birthday'],
                    'role' => 'student', // 🔒 FORÇADO
                ]);
            });

            return response()->json([
                'message' => 'Aluno cadastrado com sucesso!',
                'user' => $user->only(['id', 'name', 'email', 'role', 'cpf', 'matricula']),
            ], 201);

        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Conflito: CPF, Email ou Matrícula já cadastrados.',
            ], 409);
        } catch (\Exception $e) {
            Log::error('Erro ao criar aluno: '.$e->getMessage());

            return response()->json(['message' => 'Erro interno.'], 500);
        }
    }

    /**
     * =====================================================
     * CADASTRO DE STAFF (SOMENTE ADMIN)
     * Rota: POST /api/staff/register
     * Role SEMPRE será staff
     * =====================================================
     */
    public function storeStaff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'cpf' => 'required|string|size:11|unique:users,cpf',
            'phone' => 'required|string|max:20',
            'matricula' => 'required|string|max:50|unique:users,matricula',
            'course_id' => 'required|uuid|exists:courses,id',
            'birthday' => 'required|date_format:Y-m-d',
        ]);

        try {
            $staff = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'cpf' => $validated['cpf'],
                'phone' => $validated['phone'],
                'matricula' => $validated['matricula'],
                'course_id' => $validated['course_id'],
                'birthday' => $validated['birthday'],
                'password' => Hash::make('staff123'), // ou gerar senha depois
                'role' => 'staff', // 🔒 FORÇADO
            ]);

            return response()->json([
                'message' => 'Staff cadastrado com sucesso!',
                'user' => $staff->only(['id', 'name', 'email', 'role', 'cpf', 'matricula']),
            ], 201);

        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Conflito: CPF, Email ou Matrícula já cadastrados.',
            ], 409);
        } catch (\Exception $e) {
            Log::error('Erro ao criar staff: '.$e->getMessage());

            return response()->json(['message' => 'Erro interno.'], 500);
        }
    }

    /**
     * =====================================================
     * ATUALIZA USUÁRIO
     * =====================================================
     */
    public function update(Request $request, $id): JsonResponse
    {
        if ($request->filled('matricula')) {
            $request->merge(['matricula' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('matricula')))]);
        }

        $authUser = Auth::user();

        if (! $authUser) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        if (! $authUser->isAdmin() && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => [
                'sometimes',
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'cpf' => ['sometimes', 'required', 'string', 'size:11', Rule::unique('users', 'cpf')->ignore($user->id)],
            'phone' => 'sometimes|required|string|max:20',
            'matricula' => ['sometimes', 'required', 'string', 'regex:/^\\d{4}\\d[A-Z]{2,10}IG\\d{3,6}$/i', 'max:50', Rule::unique('users', 'matricula')->ignore($user->id)],
            'course_id' => 'sometimes|required|uuid|exists:courses,id',
            'birthday' => 'sometimes|required|date_format:Y-m-d',
            'role' => 'sometimes|required|in:student,staff,admin',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        $user->refresh();

        return response()->json([
            'message' => 'Usuário atualizado com sucesso!',
            'user' => $user->only(['id', 'name', 'email', 'role', 'cpf', 'matricula']),
        ]);
    }

    /**
     * =====================================================
     * DELETE USUÁRIO (SÓ ADMIN)
     * =====================================================
     */
    public function destroy($id): JsonResponse
    {
        $authUser = Auth::user();

        if (! $authUser) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        if (! $authUser->isAdmin()) {
            return response()->json(['message' => 'Apenas administradores podem apagar usuários.'], 403);
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        if ($authUser->id === $user->id) {
            return response()->json(['message' => 'Você não pode apagar sua própria conta.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Usuário removido com sucesso.']);
    }
}
