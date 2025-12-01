<?php

namespace App\Http\Controllers;

use App\Models\User; // Certifique-se de que o modelo User está importado
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse; // Adicione este import para o retorno 'JsonResponse'
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB; 
use App\Models\Course; // Adicione esta linha se Course for usado em relations

/**
 * Controller responsável por gerenciar operações CRUD de Usuários.
 * O método 'store' é a rota pública de registo (/api/register).
 */
class UserController extends Controller
{
    /**
     * Cadastra um novo usuário (Rota: POST /api/register).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validação (se falhar, retorna 422 automaticamente com JSON)
        // O método validate do Request lida com o retorno JSON 422
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
            ],
            // Frontend envia CPF com 11 dígitos, mas o 'size:11' no banco garante 11
            'cpf' => 'required|string|size:11|unique:users,cpf', 
            // O frontend usa 'phone' que pode ser formatado, mas aqui checamos o tamanho limpo (11)
            'phone' => 'required|string|max:20', // Mantendo max:20 para aceitar formatos
            // O frontend envia 'matricula'. Assumindo que o campo no DB é 'enrollment_number'.
            'matricula' => 'required|string|max:50|unique:users,enrollment_number', 
            'course_id' => 'required|uuid|exists:courses,id',
            'birthday' => 'required|date_format:Y-m-d',
            'role' => 'nullable|in:student,staff,admin',
        ]);
        
        Log::info('Tentativa de criação de usuário com E-mail:', ['email' => $validated['email'], 'cpf' => $validated['cpf']]);

        $user = null; // Inicializa a variável
        
        try {
            DB::transaction(function () use ($validated, &$user) { 
                // Criação do Usuário
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'cpf' => $validated['cpf'],
                    'phone' => $validated['phone'],
                    // Mapeando 'matricula' do request para 'enrollment_number' do modelo User (se este for o campo real no DB)
                    // Se o campo do DB for 'matricula', então mantenha: 'matricula' => $validated['matricula']
                    'enrollment_number' => $validated['matricula'], 
                    'course_id' => $validated['course_id'], 
                    'birthday' => $validated['birthday'],
                    'role' => $validated['role'] ?? 'student',
                ]);
            });

            if (!$user) {
                 Log::error('Falha interna: User::create retornou nulo após a transação.');
                 return response()->json(['message' => 'Erro interno. O usuário não foi persistido no banco de dados.'], 500);
            }

            Log::info('Usuário criado com sucesso:', ['user_id' => $user->id]);
            
            // 3. Retorna a resposta de sucesso
            return response()->json([
                'message' => 'Usuário criado com sucesso!',
                'user' => $user->only(['id', 'name', 'email', 'role', 'cpf', 'enrollment_number']), // Use 'enrollment_number' para a matrícula
            ], 201);
            
        } catch (QueryException $e) {
            // 4. Captura erros específicos de banco de dados (ex: Foreign Key, UNIQUE)
            Log::error('Erro de Banco de Dados (QueryException) ao criar usuário:', [
                'error' => $e->getMessage(), 
                'code' => $e->getCode(),
                'cpf' => $validated['cpf']
            ]);
            
            // Retorna 409 Conflict para o frontend
            return response()->json(['message' => 'Conflito de dados. CPF, E-mail ou Matrícula já registrados, ou Curso ID inválido.'], 409);
        }
        catch (\Exception $e) {
            // 5. Captura outras exceções genéricas
            Log::error('Erro geral ao criar usuário: ' . $e->getMessage(), ['cpf' => $validated['cpf']]);
            return response()->json(['message' => 'Erro inesperado ao criar usuário. Detalhes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Atualiza um usuário (Admin ou o próprio usuário).
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id O UUID do usuário a ser atualizado
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            /** @var \App\Models\User|null $authUser */ 
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json(['message' => 'Usuário não autenticado.'], 401);
            }

            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado.'], 404);
            }

            // Assumindo que o método isAdmin() existe no modelo User
            if (!$authUser->isAdmin() && $authUser->id !== $user->id) {
                return response()->json(['message' => 'Acesso negado.'], 403);
            }

            // Definição das regras de validação para a atualização
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => [
                    'sometimes',
                    'required',
                    'string',
                    'confirmed',
                    Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
                ],
                'cpf' => ['sometimes', 'required', 'string', 'size:11', Rule::unique('users', 'cpf')->ignore($user->id)],
                'phone' => 'sometimes|required|string|max:20', // Mantendo max:20 para aceitar formatos
                'matricula' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('users', 'enrollment_number')->ignore($user->id, 'enrollment_number')], // Correção na coluna unique
                'course_id' => 'sometimes|required|uuid|exists:courses,id',
                'birthday' => 'sometimes|required|date_format:Y-m-d',
                'role' => 'sometimes|required|in:student,staff,admin',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }
            
            // Mapeando 'matricula' para 'enrollment_number' antes do update
            if (isset($validated['matricula'])) {
                $validated['enrollment_number'] = $validated['matricula'];
                unset($validated['matricula']); // Remove a chave 'matricula' se o campo DB for 'enrollment_number'
            }

            $user->update($validated);

            // Recarregar o usuário para garantir que os dados atualizados estejam presentes
            $user->refresh();

            return response()->json([
                'message' => 'Usuário atualizado com sucesso!',
                'user' => $user->only(['id', 'name', 'email', 'role', 'cpf', 'enrollment_number']),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar usuário: ' . $e->getMessage());
            // Retorna 409 Conflict se for um erro de chave única
            if ($e instanceof QueryException && str_contains($e->getMessage(), 'Duplicate entry')) {
                 return response()->json(['message' => 'Conflito de dados. Algum valor único (E-mail, CPF, Matrícula) já está em uso.'], 409);
            }
            return response()->json(['message' => 'Erro ao atualizar usuário.'], 500);
        }
    }

    /**
     * Remove um usuário.
     *
     * @param string $id O UUID do usuário a ser removido
     * @return \Illuminate\Http\JsonResponse
     */
    // Adicionando o método destroy para completar a estrutura CRUD (opcional, mas bom ter)
    public function destroy($id): JsonResponse
    {
        /** @var \App\Models\User|null $authUser */ 
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }
        
        // Regra de segurança: Apenas Admin pode remover usuários
        if (!$authUser->isAdmin()) {
            return response()->json(['message' => 'Permissão negada. Apenas administradores podem excluir usuários.'], 403);
        }

        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado.'], 404);
            }

            // Prevenção: Não permitir que o Admin apague a si mesmo
            if ($authUser->id === $user->id) {
                 return response()->json(['message' => 'Você não pode excluir sua própria conta de administrador.'], 403);
            }

            $user->delete();

            Log::warning('Usuário excluído:', ['user_id' => $id, 'deleted_by' => $authUser->id]);
            return response()->json(['message' => 'Usuário excluído com sucesso.'], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir usuário: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao excluir usuário.'], 500);
        }
    }
}