<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
  public function login(Request $request)
  {
    $validated = $request->validate([
      'email' => 'required|email|max:255',
      'password' => 'required|string|max:255'
    ]);

    if (Auth::attempt($validated)) {
      $user = User::where('email', $validated['email'])->first();

      $token = $user
        ->createToken('api-token', ['post:read', 'post:create', 'post:update'])
        ->plainTextToken;

      return response()->json(['token' => $token]);
    }

    return response()->json(['msg' => 'Credenciais inválidas'],  401);
  }

  public function logout(Request $request)
  {
    $token = $request->bearerToken();

    if (!$token) {
      return response()->json(['msg' => 'Token não informado'], 400);
    }

    $access_token = PersonalAccessToken::findToken($token);

    if (!$access_token) {
      return response()->json(['msg' => 'Token inválido'], 400);
    }

    $access_token->delete();

    return response()->json(['msg' => 'Logout realizado com sucesso'], 200);
  }
}
