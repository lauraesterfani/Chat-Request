<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
  public function login(Request $request)
  {
    $credentials = $request->only('cpf', 'password');

    if (!$token = JWTAuth::attempt($credentials)) {
      return response()->json(['msg' => 'Credenciais inválidas'], 401);
    }

    $user = Auth::user();

    return response()->json([
      'user' => $user,
      'token' => $token
    ]);
  }

  public function setEnrollment(string $enrollment_id)
  {
    $user = Auth::user();

    try {
      $enrollmentRecord = Enrollment::find($enrollment_id);

      if (!$enrollmentRecord) {
        return response()->json(['msg' => 'Matrícula não encontrada!'], 404);
      }

      if ($enrollmentRecord->user_id !== $user->id) {
        return response()->json(['msg' => 'Essa matrícula não pertence a esse usuário!'], 403);
      }

      $customClaims = ['enrollment_id' => $enrollment_id];
      $newToken = JWTAuth::claims($customClaims)->fromUser($user);

      return response()->json([
        'msg' => 'Matrícula alterada com sucesso!',
        'token' => $newToken,
      ]);
    } catch (\Exception $e) {
      Log::error('Erro ao mudar matrícula: ' . $e->getMessage());

      return response()->json([
        'msg' => 'Erro interno. Tente novamente mais tarde.',
      ], 500);
    }
  }

  public function me()
  {
    $user = Auth::user();
    $payload = JWTAuth::parseToken()->getPayload();
    $enrollment_id = $payload->get('enrollment_id', null);

    return response()->json([
      'user' => $user,
      'enrollment_id' => $enrollment_id,
    ]);
  }



  public function refresh()
  {
    $token = JWTAuth::getToken();
    $payload = JWTAuth::getPayload($token);
    $enrollment = $payload->get('enrollment');
    $newToken = JWTAuth::claims(['enrollment' => $enrollment])->refresh($token);

    return response()->json([
      'token' => $newToken
    ]);
  }
}
