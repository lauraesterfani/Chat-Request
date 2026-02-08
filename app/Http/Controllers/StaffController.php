<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // <--- IMPORTANTE: Para gerar o ID

class StaffController extends Controller
{
    // --- LISTAGEM ---
    public function index()
    {
        try {
            $staffs = DB::table('users')
                        ->where('role', '!=', 'student')
                        ->select('id', 'name', 'email', 'role') 
                        ->get();

            return response()->json($staffs);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // --- CRIAÇÃO COM UUID ---
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required',
            ]);

            // Gera um ID único (UUID) manualmente
            $uuid = (string) Str::uuid();

            // Insere no banco
            DB::table('users')->insert([
                'id' => $uuid, // <--- AQUI ESTAVA FALTANDO!
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'matricula' => 'STAFF-' . rand(1000, 9999),
                'cpf' => '000.000.000-' . rand(10, 99),
                'phone' => '(00) 00000-0000',
                'birthday' => '2000-01-01',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Busca o usuário criado para devolver ao front
            $newUser = DB::table('users')->where('id', $uuid)->first();
            
            return response()->json($newUser, 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'ERRO NO SERVIDOR: ' . $e->getMessage()
            ], 500);
        }
    }

    // --- EXCLUSÃO ---
    public function destroy($id)
    {
        try {
            if (auth()->id() == $id) {
                throw new \Exception('Você não pode se excluir.');
            }
            DB::table('users')->where('id', $id)->delete();
            return response()->json(['message' => 'Excluído.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}