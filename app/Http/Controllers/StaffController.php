<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    /**
     * Lista todos os administradores/staff
     */
    public function index()
    {
        // Busca usuários que NÃO são alunos (ou seja, admin, staff, cradt)
        $staffs = User::where('role', '!=', 'student')->get();
        return response()->json($staffs);
    }

    /**
     * Cria um novo funcionário (Admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin', // Ou 'staff', dependendo de como você configurou
            'matricula' => 'STAFF-' . rand(1000, 9999), // Gera uma matrícula fictícia
        ]);

        return response()->json($user, 201);
    }

    /**
     * Remove um funcionário
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Segurança básica: não deixar deletar a si mesmo (opcional no front, mas bom no back)
        if ($user->id === auth()->id()) {
             return response()->json(['error' => 'Você não pode se deletar.'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Funcionário removido.']);
    }
}