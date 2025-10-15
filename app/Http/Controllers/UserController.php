<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    // ... (Método index() não alterado)
public function index(Request $request)
    {
        $user = Auth::user();

        // Se o diagnóstico não pegou 401, removemos os dd()
        if (!$user) {
             // Retorno padrão para não autenticado (401)
             return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        // --- LÓGICA DE FILTRAGEM BASEADA NO PAPEL (Sem Policy, 100% no Controller) ---
        
        // 1. Inicia a query
        $requestsQuery = RequestModel::query()->with('user');
        
        // 2. Filtra baseado no papel
        if ($user->isStudent()) {
            // Se for Student, aplica o filtro RIGOROSO: só vê os próprios requerimentos
            $requestsQuery->where('user_id', $user->id);
            
        } elseif (!$user->isAdmin() && !$user->isStaff()) {
            // Se o papel não for reconhecido (nem Student, nem Staff, nem Admin)
            return response()->json(['message' => 'Acesso negado. Papel de usuário inválido.'], 403);
        }
        
        // Se for Admin ou Staff, a query continua sem filtro, permitindo que eles vejam todos os requerimentos.

        // 3. Aplica paginação e retorna
        // ... (resto da sua lógica de listagem)

        $requests = $requestsQuery->paginate(10);
        
        return RequestResource::collection($requests);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id),
                    'max:255'
                ],
                'password' => [
                    'sometimes',
                    'required',
                    'string',
                    'confirmed',
                    'max:255',
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
                'user_type' => 'sometimes|required|in:student,staff',
                'birthday' => 'sometimes|required|date_format:Y-m-d',
            ]);

            // REMOVIDO: Hash::make() manual. O 'password' => 'hashed' no Modelo cuida disso.

            $user->update($validated);

            return response()->json($user, 200);
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating user'], 500);
        }
    }
    // ... (Métodos show(), destroy() e validateToken() não alterados)
}
