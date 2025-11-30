<?php

namespace App\Http\Controllers;

use App\Models\User; // Certifique-se de que o modelo User está importado
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB; 
use App\Models\Course; // Adicione esta linha se Course for usado em relations

class UserController extends Controller
{
    /**
     * Cadastra um novo usuário.
     */
    public function store(Request $request)
    {
        // 1. Validação (se falhar, retorna 422 automaticamente)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
            ],
            'cpf' => 'required|string|size:11|unique:users',
            'phone' => 'required|string|size:11',
            'matricula' => 'required|string|max:50|unique:users', 
            'course_id' => 'required|uuid|exists:courses,id',
            'birthday' => 'required|date_format:Y-m-d',
            'role' => 'nullable|in:student,staff,admin',
        ]);
        
        Log::info('Tentativa de criação de usuário com E-mail:', ['email' => $validated['email'], 'cpf' => $validated['cpf']]);

        $user = null; // Inicializa a variável
        
        try {
            DB::transaction(function () use ($validated, &$user) { 
                // AQUI o Mass Assignment agora deve funcionar se o modelo User tiver os campos no $fillable
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'cpf' => $validated['cpf'],
                    'phone' => $validated['phone'],
                    'matricula' => $validated['matricula'], 
                    'course_id' => $validated['course_id'], 
                    'birthday' => $validated['birthday'],
                    'role' => $validated['role'] ?? 'student',
                ]);
            });

            // 2. CHECAGEM CRÍTICA: Se a transação terminou sem lançar exceção, mas o objeto $user é nulo (o que é raro, mas possível em falhas de ORM), retorne erro.
            if (!$user) {
                 Log::error('Falha interna: User::create retornou nulo após a transação.');
                 return response()->json(['message' => 'Erro interno. O usuário não foi persistido no banco de dados.'], 500);
            }

            Log::info('Usuário criado com sucesso:', ['user_id' => $user->id]);
            
            // 3. Retorna a resposta de sucesso
            return response()->json([
                'message' => 'Usuário criado com sucesso!',
                'user' => $user->only(['id', 'name', 'email', 'role', 'cpf', 'matricula']),
            ], 201);
            
        } catch (QueryException $e) {
            // 4. Captura erros específicos de banco de dados (ex: Foreign Key, UNIQUE)
            Log::error('Erro de Banco de Dados (QueryException) ao criar usuário:', [
                'error' => $e->getMessage(), 
                'code' => $e->getCode(),
                'cpf' => $validated['cpf']
            ]);
            
            // Retorna 409 Conflict para o frontend
            return response()->json(['message' => 'Conflito de dados. CPF ou Matrícula já registrados, ou Course ID inválido.'], 409);
        }
        catch (\Exception $e) {
            // 5. Captura outras exceções genéricas (incluindo falha no isAdmin se persistir)
            Log::error('Erro geral ao criar usuário: ' . $e->getMessage(), ['cpf' => $validated['cpf']]);
            return response()->json(['message' => 'Erro inesperado ao criar usuário. Detalhes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Atualiza um usuário (Admin ou o próprio usuário).
     * 🔥 Corrigido o Type Hint para forçar o uso do modelo User.
     */
    public function update(Request $request, $id)
    {
        try {
            // 🔥 Type Hint na variável para o IDE e Runtime
            /** @var \App\Models\User|null $authUser */ 
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json(['message' => 'Usuário não autenticado.'], 401);
            }

            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado.'], 404);
            }

            // O método isAdmin() está definido no modelo User, que é o tipo esperado para $authUser.
            if (!$authUser->isAdmin() && $authUser->id !== $user->id) {
                return response()->json(['message' => 'Acesso negado.'], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
                'password' => [
                    'sometimes',
                    'required',
                    'string',
                    'confirmed',
                    Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
                ],
                'cpf' => ['sometimes', 'required', 'string', 'size:11', Rule::unique('users')->ignore($user->id)],
                'phone' => 'sometimes|required|string|size:11',
                'matricula' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
                'course_id' => 'sometimes|required|uuid|exists:courses,id',
                'birthday' => 'sometimes|required|date_format:Y-m-d',
                'role' => 'sometimes|required|in:student,staff,admin',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'message' => 'Usuário atualizado com sucesso!',
                'user' => $user->only(['id', 'name', 'email', 'role', 'cpf', 'matricula']), // Simplificado, removendo load('enrollments') para evitar falha se a relação não estiver correta
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar usuário: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao atualizar usuário.'], 500);
        }
    }

    // ... (funções destroy e validateToken)
}