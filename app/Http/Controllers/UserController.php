<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $users = User::all();
            return response()->json($users, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching users'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Mapeia o user_type do input para o role_id numérico do banco de dados
        $roleMapping = [
            'admin' => 1,
            'staff' => 2,
            'student' => 3,
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
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
            'cpf' => 'required|string|size:11|unique:users,cpf',
            'birthday' => 'required|date',
            'phone' => 'required|string|size:11',
            // A validação agora aceita 'admin', 'student' ou 'staff'
            'user_type' => 'required|in:admin,student,staff'
        ]);

        try {
            $validated['password'] = Hash::make($validated['password']);

            // Adiciona o role_id baseado no user_type
            $validated['role_id'] = $roleMapping[$validated['user_type']];

            // Remove o user_type do array antes de criar o usuário
            unset($validated['user_type']);

            $user = User::create($validated);

            return response()->json([
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type, // O accessor vai buscar essa info
                    'role_id' => $user->role_id,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());

            // Alterado para retornar o erro detalhado da exceção
            return response()->json([
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            return response()->json($user, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching user'], 500);
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

            // Mapeia o user_type do input para o role_id numérico do banco de dados
            $roleMapping = [
                'admin' => 1,
                'staff' => 2,
                'student' => 3,
            ];

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
                'birthday' => 'sometimes|required|date',
                'phone' => 'sometimes|required|string|size:11',
                // Atualiza a validação para aceitar admin, student ou staff
                'user_type' => 'sometimes|required|in:admin,student,staff'
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }
            
            // Adiciona a lógica para atualizar o role_id se o user_type for enviado
            if (isset($validated['user_type'])) {
                $validated['role_id'] = $roleMapping[$validated['user_type']];
                // Remove o user_type do array antes de atualizar o usuário
                unset($validated['user_type']);
            }

            $user->update($validated);

            return response()->json($user, 200);
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating user'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.'
                ], 404);
            }

            $user->delete();

            return response()->noContent(); // 204 No Content
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error deleting user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
