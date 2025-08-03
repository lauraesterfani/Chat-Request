<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

  public function changeEnrollment($enrollment)
  {
    $user = Auth::user();
    $customClaims = ['enrollment' => $enrollment];
    $newToken = JWTAuth::claims($customClaims)->fromUser($user);

    return response()->json([
      'msg' => 'Matrícula alterada com sucesso!',
      'token' => $newToken,
    ]);
  }

  public function me()
  {
    $user = Auth::user();
    $payload = JWTAuth::parseToken()->getPayload();
    $enrollment = $payload->get('enrollment', null);

    return response()->json([
      'user' => $user,
      'enrollment' => $enrollment,
    ]);
  }

  public function logout()
  {
    JWTAuth::invalidate(JWTAuth::getToken());
    return response()->json(['msg' => 'Logout realizado com sucesso']);
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
