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
            // ESTA LINHA JÁ ESTAVA CORRETA
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
        try {
            // 1. Validação
            $credentials = $request->validate([
                'cpf' => 'required|string|size:11',
                'password' => 'required|string|min:6',
            ]);
            
            // 2. Tenta obter o token usando o guard 'api' (JWT)
            if ($token = auth('api')->attempt($credentials)) {
                
                // Sucesso na Autenticação JWT
                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    // CORREÇÃO: Usando JWTAuth::factory() para evitar o erro Undefined method
                    'expires_in' => JWTAuth::factory()->getTTL() * 60, 
                    'user' => auth('api')->user()
                ], 200);

            }
            
            // 3. FALLBACK: Se o JWT falhou (ex: config errada), tentamos uma busca manual
            $user = User::where('cpf', $credentials['cpf'])->first();

            if ($user && Hash::check($credentials['password'], $user->password)) {
                
                // Se a senha estiver correta, tentamos gerar o token diretamente do usuário
                if ($token = JWTAuth::fromUser($user)) {
                    
                    Log::warning("JWT 'attempt' falhou, mas autenticação manual funcionou. Configuração JWT requer revisão.");

                    return response()->json([
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        // CORREÇÃO: Usando JWTAuth::factory() para evitar o erro Undefined method
                        'expires_in' => JWTAuth::factory()->getTTL() * 60, 
                        'user' => $user
                    ], 200);
                }
            }


            // Se tudo falhar
            return response()->json([
                'message' => 'Credenciais Inválidas ou Erro na Geração do Token.'
            ], 401);


        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            // O 500 agora só deve ocorrer se houver um erro de PHP, não de credencial
            return response()->json(['message' => 'Erro interno ao tentar autenticar. Verifique o log do Laravel.'], 500);
        }
    }
    /**
     * REMOVIDO: O método changeEnrollment foi removido por não ser mais necessário.
     */

    /**
     * Obtém o usuário autenticado.
     */
    public function me(Request $request) // <-- Adicione (Request $request)
    {
        // 1. Loga o header de autorização recebido
        $authHeader = $request->header('Authorization');
        Log::info('*** DIAGNÓSTICO JWT ***');
        Log::info('Header Authorization Recebido: ' . $authHeader);
        
        try {
            // Tenta forçar a busca do token
            $token = JWTAuth::parseToken();
            $user = $token->authenticate();

            if (!$user) {
                // Caso o token seja válido, mas o usuário não seja encontrado (ex: provider errado)
                Log::error("ERRO FATAL: Token válido, mas usuário não encontrado no DB. Verifique config/auth.php.");
                return response()->json(['msg' => 'Unauthorized - User not found in DB.'], 401);
            }
            
            Log::info('SUCESSO: Usuário ID ' . $user->id . ' autenticado.');

            // Lógica de resposta original
            return response()->json([
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            // Este catch pega TokenExpired, TokenInvalid, TokenBlacklisted, ou TokenNotPresent
            Log::error("ERRO 401 Detalhe: " . $e->getMessage() . ". O token não passou na validação.");
            return response()->json(['msg' => 'Acesso não autorizado. O token está ausente ou inválido.'], 401);
        }
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
