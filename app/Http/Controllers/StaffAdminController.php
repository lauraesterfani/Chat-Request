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
        $staffs = StaffAdmin::select('id', 'name', 'email', 'role', 'cpf', 'phone')->get();
        return response()->json($staffs);
    }

    public function admins()
    {
        return StaffAdmin::where('role', 'admin')->get();
    }

    public function destroy($id)
{
    $staff = StaffAdmin::findOrFail($id);
    $staff->delete();

    return response()->json(['message' => 'Removido com sucesso']);
}

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name'  => 'required|string',
                'email' => 'required|email|unique:staff_admins,email',
                'role'  => 'required|string|in:admin,staff,coordenacao',
                'cpf'   => 'nullable|string|unique:staff_admins,cpf',
                'course_id' => 'nullable|exists:courses,id'
            ]);

            // gera senha aleatória
            $tempPassword = \Illuminate\Support\Str::random(10);
            $data['password'] = Hash::make($tempPassword);
            $data['must_change_password'] = true; // flag para redefinição obrigatória

            $staff = StaffAdmin::create($data);

            // retorna a senha temporária junto com os dados
            return response()->json([
                'message' => 'Criado com sucesso!',
                'user' => $staff,
                'temp_password' => $tempPassword
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error_tecnico' => $e->getMessage()], 500);
        }
    }
}
