<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User; // Certifique-se de que o modelo User está importado

class AuthController extends Controller
{
    /**
     * Lidar com o processo de login usando CPF e Password.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $request->validate([
            'cpf' => ['required', 'string', 'max:11'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('cpf', 'password');

        // 1. Tentar autenticar o usuário e gerar o token JWT no guard 'api'
        $token = auth('api')->attempt($credentials);

        // 2. Se a autenticação falhar
        if (!$token) {
            throw ValidationException::withMessages([
                'cpf' => [__('Credenciais inválidas. Verifique o CPF e a senha.')],
            ]);
        }

        // 3. Se a autenticação for bem-sucedida, o token foi gerado.
        
        // 4. Obter o objeto User do guard.
        $user = auth('api')->user(); 
        
        // Verificação de segurança: Se, por alguma razão, o user ainda for null, 
        // procuramos o utilizador manualmente pelo CPF.
        if (!$user) {
            $user = User::where('cpf', $request->cpf)->first();
        }

        // 5. Retornar a resposta JSON
        return response()->json([
            'user' => $user, 
            'token' => $token, 
            'message' => 'Login bem-sucedido.',
            'expires_in' => auth('api')->factory()->getTTL() * 60, 
        ], 200);
    }
    
    /**
     * Get the authenticated User's details.
     * Esta função é usada pela rota /api/me para verificar a sessão (o token JWT).
     * O middleware 'auth:api' garante que o token é válido antes de chamar este método.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        // O Laravel (com JWT-Auth) detetou o utilizador pelo token.
        // auth('api')->user() retorna o objeto do utilizador.
        $user = auth('api')->user(); 

        if (!$user) {
            // Isto não deveria acontecer, pois a rota está protegida pelo middleware 'auth:api'.
            return response()->json(['message' => 'Token inválido ou expirado.'], 401);
        }

        // Retorna os dados do utilizador.
        return response()->json($user);
    }
    
    // NOTA: Os métodos 'logout', 'refresh' e 'changeEnrollment' estão em falta 
    // neste ficheiro, mas não causaram o erro atual. Se estiverem noutro local, 
    // certifique-se de que estão a funcionar. O erro atual foi apenas por causa do 'me()'.
}