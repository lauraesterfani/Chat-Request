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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'password' => [
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
                'cpf' => 'required|string|size:11|unique:users,cpf',
                'phone' => 'required|string|size:11',
                'user_type' => 'required|in:student,staff',
                'birthday' => 'required|date_format:Y-m-d',
            ]);

            // Cria o UUID para o ID
            $validated['id'] = (string) Str::uuid();
            
            // GARANTINDO QUE CAMPOS NULOS NÃO CAUSEM ERRO
            // O hash da senha é feito automaticamente pelo Modelo
            $validated['email_verified_at'] = null; // Garante que campos não nulos sejam preenchidos corretamente

            // Cria o usuário (Mass Assignment)
            $user = User::create($validated);

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            // ESTE É O PONTO MAIS IMPORTANTE: VERIFIQUE O ARQUIVO DE LOG!
            Log::error('Error creating user: ' . $e->getMessage());

            return response()->json([
                'message' => 'Internal error while creating user. Please try again later.'
            ], 500);
        }
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
