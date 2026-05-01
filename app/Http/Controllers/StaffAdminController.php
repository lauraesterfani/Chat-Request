<?php

namespace App\Http\Controllers;

use App\Models\StaffAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;

class StaffAdminController extends Controller
{
    public function index()
    {
        // Usando query direta para garantir que não há cache de resultado
        $staffs = StaffAdmin::select('id', 'name', 'email', 'role', 'cpf', 'phone')->get();
        return response()->json($staffs);
    }
public function admins()
{
    return StaffAdmin::where('role', 'admin')->get();
}

    public function store(Request $request)
{
    try {
        // Removi a senha do validate, pois agora ela é aleatória
        $data = $request->validate([
            'name'  => 'required|string',
            'email' => 'required|email|unique:staff_admins,email',
            'role'  => 'required|string',
            'cpf'   => 'nullable|string|unique:staff_admins,cpf',
        ]);

        $tempPassword = \Illuminate\Support\Str::random(10);
        $data['password'] = \Illuminate\Support\Facades\Hash::make($tempPassword);

        $staff = StaffAdmin::create($data);

        return response()->json([
            'message' => 'Criado com sucesso!',
            'temp_password' => $tempPassword,
            'user' => $staff
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Se cair aqui, o Laravel vai dizer EXATAMENTE qual campo está duplicado
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        // Se cair aqui, é um erro de banco ou de código (Ex: coluna faltando)
        return response()->json(['error_tecnico' => $e->getMessage()], 500);
    }
}
}