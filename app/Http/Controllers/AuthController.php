<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
// REMOVIDO: use App\Models\Enrollment;
use App\Models\User; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; 
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct()
    {
        // Aplica o middleware de autenticação, exceto para login E register
        $this->middleware('auth:api', ['except' => ['login', 'register']]); 
    }

    protected function respondWithToken($token)
    {
        // REMOVIDO: Lógica de active_enrollment_id
        
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Lida com o registro de um novo usuário (sem criação de matrícula).
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|unique:users|max:14',
            'email' => 'required|string|email|unique:users|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'cpf' => $request->cpf,
                'email' => $request->email,
                'password' => $request->password,
            ]);

            // REMOVIDO: Lógica de criação de Matrícula Padrão

            return response()->json([
                'msg' => 'Usuário criado com sucesso.',
                'user_id' => $user->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar usuário: ' . $e->getMessage());
            return response()->json(['msg' => 'Erro interno ao criar usuário. Detalhe: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Lida com o login do usuário.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cpf' => 'required|string|max:14',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only('cpf', 'password');
        $token = null; 

        try {
            // Tenta logar e gerar o token
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['msg' => 'Credenciais inválidas'], 401);
            }
        } catch (JWTException $e) {
            Log::error('Erro ao tentar gerar token JWT: ' . $e->getMessage());
            return response()->json(['msg' => 'Não foi possível criar o token no servidor.'], 500);
        }
        
        // REMOVIDO: Toda a lógica de verificação de matrícula ativa e invalidação de token.
        
        // Login bem-sucedido. Retorna o token.
        return $this->respondWithToken($token);
    }
    
    /**
     * REMOVIDO: O método changeEnrollment foi removido por não ser mais necessário.
     */

    /**
     * Obtém o usuário autenticado.
     */
    public function me()
    {
        $user = Auth::user();
        
        // REMOVIDO: Lógica para pegar o activeEnrollmentId do payload
        
        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Faz logout (invalida o token).
     */
    public function logout()
    {
        try {
            $token = JWTAuth::getToken();
            
            if ($token) {
                 JWTAuth::invalidate($token);
            }
            return response()->json(['msg' => 'Logout realizado com sucesso']);

        } catch (TokenExpiredException | TokenInvalidException $e) {
            return response()->json(['msg' => 'Logout realizado com sucesso (Token expirado/inválido)']);
        } catch (JWTException $e) {
            return response()->json(['msg' => 'Logout realizado com sucesso (Token ausente)']);
        }
    }

    /**
     * Renova um token.
     */
    public function refresh()
    {
        try {
            // Renova o token existente
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return $this->respondWithToken($newToken);
        } catch (TokenInvalidException $e) {
            return response()->json(['msg' => 'O token é inválido. Por favor, faça login novamente.'], 401);
        } catch (TokenExpiredException $e) {
            // Tenta renovar mesmo que esteja expirado
            try {
                $newToken = JWTAuth::refresh(JWTAuth::getToken());
                return $this->respondWithToken($newToken);
            } catch (JWTException $e) {
                 return response()->json(['msg' => 'O token não pode ser renovado. Por favor, faça login novamente.'], 401);
            }
        } catch (JWTException $e) {
             return response()->json(['msg' => 'Acesso não autorizado. Token ausente.'], 401);
        }
    }
}
