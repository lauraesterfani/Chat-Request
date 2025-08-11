<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Aluno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registra um novo aluno e retorna um token JWT.
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'nome_completo'   => 'required|string|max:255',
            'email'           => 'required|string|email|max:255|unique:alunos',
            'password'        => 'required|string|min:8|confirmed',
            'cpf'             => 'required|string|unique:alunos',
            'telefone'        => 'nullable|string|max:20',
            'identidade'      => 'required|string|max:20',
            'orgao_expedidor' => 'required|string|max:20',
        ]);

        $aluno = Aluno::create([
            'nome_completo'   => $validatedData['nome_completo'],
            'email'           => $validatedData['email'],
            'password'        => Hash::make($validatedData['password']),
            'cpf'             => preg_replace('/[^0-9]/', '', $validatedData['cpf']),
            'telefone'        => $validatedData['telefone'],
            'identidade'      => $validatedData['identidade'],
            'orgao_expedidor' => $validatedData['orgao_expedidor'],
            'tipo_usuario'    => 'Estudante',
        ]);

        // Após criar, tenta fazer login para gerar o token JWT
        $credentials = ['cpf' => $aluno->cpf, 'password' => $request->password];
        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Não foi possível autenticar após o registro.'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Autentica um aluno com CPF e senha e retorna um token JWT.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'cpf'      => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials['cpf'] = preg_replace('/[^0-9]/', '', $credentials['cpf']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'CPF ou senha inválidos.'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Retorna os dados do usuário autenticado.
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Faz o logout do usuário (invalida o token).
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Logout realizado com sucesso!']);
    }

    /**
     * Valida o token de autenticação atual.
     */
    public function validateToken()
    {
        return response()->json(['valid' => true]);
    }

    /**
     * Formata a resposta JSON com o token de acesso.
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => auth('api')->user()
        ]);
    }
}
