<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\StaffAdmin;

class StaffAdminController extends Controller
{
    /**
     * Cadastrar novo Admin/Staff
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:staff_admins',
            'phone' => 'required|string|max:20',
            'cpf'   => 'required|string|unique:staff_admins',
            'role'  => 'required|in:admin,staff',
        ]);

        // Gerar senha aleatória
        $password = Str::random(10);

        // Criar usuário
        $user = StaffAdmin::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'cpf'      => $request->cpf,
            'role'     => $request->role,
            'password' => Hash::make($password),
        ]);

        // Enviar senha por e-mail (texto puro)
        Mail::raw("Olá {$user->name}, sua conta foi criada com sucesso.\n\nSua senha de acesso é: {$password}\n\nPor favor, altere sua senha após o primeiro login.", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Sua senha de acesso');
        });

        return response()->json([
            'message' => 'Cadastro realizado com sucesso! A senha foi enviada por e-mail.'
        ]);
    }
public function destroy($id)
{
    $staff = StaffAdmin::findOrFail($id);
    $staff->delete();

    return response()->json(['message' => 'Removido com sucesso']);
}
    /**
     * Alterar senha
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Senha atual incorreta'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Senha alterada com sucesso!']);
    }

    public function index()
{
    return StaffAdmin::all();
}

}
