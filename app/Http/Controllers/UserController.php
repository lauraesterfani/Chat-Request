<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Models\Course;

class UserController extends Controller
{
    /**
     * Cadastra um novo usuário (POST /api/register)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
            ],
            'cpf'       => 'required|string|size:11|unique:users,cpf',
            'phone'     => 'required|string|max:20',
            'matricula' => 'required|string|max:50|unique:users,matricula',
            'course_id' => 'required|uuid|exists:courses,id',
            'birthday'  => 'required|date_format:Y-m-d',
            'role'      => 'nullable|in:student,staff,admin',
        ]);

        Log::info('Tentativa de criação de usuário:', [
            'email' => $validated['email'],
            'cpf'   => $validated['cpf']
        ]);

        $user = null;

        try {
            DB::transaction(function () use ($validated, &$user) {

                $user = User::create([
                    'name'      => $validated['name'],
                    'email'     => $validated['email'],
                    'password'  => Hash::make($validated['password']),
                    'cpf'       => $validated['cpf'],
                    'phone'     => $validated['phone'],
                    'matricula' => $validated['matricula'],  // 👈 AQUI ESTÁ O AJUSTE CORRETO
                    'course_id' => $validated['course_id'],
                    'birthday'  => $validated['birthday'],
                    'role'      => $validated['role'] ?? 'student',
                ]);

            });

            if (!$user) {
                Log::error('User::create retornou null.');
                return response()->json(['message' => 'Erro interno ao salvar usuário.'], 500);
            }

            Log::info('Usuário criado com sucesso:', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Usuário criado com sucesso!',
                'user'    => $user->only(['id', 'name', 'email', 'role', 'cpf', 'matricula']),
            ], 201);

        } catch (QueryException $e) {

            Log::error('Erro SQL ao criar usuário:', [
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
                'cpf'   => $validated['cpf']
            ]);

            return response()->json([
                'message' => 'Conflito: CPF, Email ou Matrícula já cadastrados.'
            ], 409);
        }
        catch (\Exception $e) {

            Log::error('Erro geral ao criar usuário: '.$e->getMessage());

            return response()->json([
                'message' => 'Erro inesperado: '.$e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza o usuário
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json(['message' => 'Não autenticado.'], 401);
            }

            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado.'], 404);
            }

            if (!$authUser->isAdmin() && $authUser->id !== $user->id) {
                return response()->json(['message' => 'Acesso negado.'], 403);
            }

            $validated = $request->validate([
                'name'      => 'sometimes|required|string|max:255',
                'email'     => ['sometimes','required','email',Rule::unique('users','email')->ignore($user->id)],
                'password'  => [
                    'sometimes',
                    'required',
                    'confirmed',
                    Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
                ],
                'cpf'       => ['sometimes','required','string','size:11',Rule::unique('users','cpf')->ignore($user->id)],
                'phone'     => 'sometimes|required|string|max:20',
                'matricula' => ['sometimes','required','string','max:50',Rule::unique('users','matricula')->ignore($user->id)],
                'course_id' => 'sometimes|required|uuid|exists:courses,id',
                'birthday'  => 'sometimes|required|date_format:Y-m-d',
                'role'      => 'sometimes|required|in:student,staff,admin',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);
            $user->refresh();

            return response()->json([
                'message' => 'Usuário atualizado com sucesso!',
                'user'    => $user->only(['id','name','email','role','cpf','matricula'])
            ]);

        } catch (\Exception $e) {

            if ($e instanceof QueryException && str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json(['message'=>'Conflito: email, CPF ou matrícula já usados.'],409);
            }

            Log::error('Erro ao atualizar usuário: '.$e->getMessage());

            return response()->json(['message'=>'Erro ao atualizar usuário.'],500);
        }
    }

    /**
     * Deleta um usuário
     */
    public function destroy($id): JsonResponse
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json(['message'=>'Não autenticado.'],401);
        }

        if (!$authUser->isAdmin()) {
            return response()->json(['message'=>'Apenas administradores podem apagar usuários.'],403);
        }

        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['message'=>'Usuário não encontrado.'],404);
            }

            if ($authUser->id === $user->id) {
                return response()->json(['message'=>'Você não pode apagar sua própria conta.'],403);
            }

            $user->delete();

            return response()->json(['message'=>'Usuário removido com sucesso.'],200);

        } catch (\Exception $e) {

            Log::error('Erro ao excluir usuário: '.$e->getMessage());

            return response()->json(['message'=>'Erro ao excluir usuário.'],500);
        }
    }
}
