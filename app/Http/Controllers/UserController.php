<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    try {
      $users = User::all();
      return response()->json($users, 200);
    } catch (\Exception $e) {
      Log::error('Error fetching users: ' . $e->getMessage());
      return response()->json(['message' => 'Error fetching users'], 500);
    }
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|email|unique:users,email',
      'password' => [
        'required',
        'string',
        'confirmed',
        Password::min(8)
          ->mixedCase()
          ->numbers()
          ->symbols()
          ->uncompromised()
      ],
      'cpf' => 'required|string|size:11|unique:users,cpf',
      'birthday' => 'required|date',
      'phone' => 'required|string|size:11',
      'user_type' => 'required|in:student,staff'
    ]);

    try {
      $validated['password'] = Hash::make($validated['password']);

      $user = User::create($validated);

      return response()->json([
        'message' => 'User created successfully',
        'user' => $user
      ], 201);
    } catch (\Exception $e) {
      Log::error('Error creating user: ' . $e->getMessage());

      return response()->json([
        'message' => 'Internal error while creating user. Please try again later.'
      ], 500);
    }
  }

  /**
   * Display the specified resource.
   */
  public function show($id)
  {
    try {
      $user = User::find($id);

      if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
      }

      return response()->json($user, 200);
    } catch (\Exception $e) {
      Log::error('Error fetching user: ' . $e->getMessage());
      return response()->json(['message' => 'Error fetching user'], 500);
    }
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, $id)
  {
    try {
      $user = User::find($id);

      if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
      }

      $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'email' => [
          'sometimes',
          'required',
          'email',
          Rule::unique('users')->ignore($user->id),
        ],
        'password' => [
          'sometimes',
          'required',
          'string',
          'confirmed',
          Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised()
        ],
        'cpf' => [
          'sometimes',
          'required',
          'string',
          'size:11',
          Rule::unique('users')->ignore($user->id),
        ],
        'birthday' => 'sometimes|required|date',
        'phone' => 'sometimes|required|string|size:11',
        'user_type' => 'sometimes|required|in:student,staff'
      ]);

      if (isset($validated['password'])) {
        $validated['password'] = Hash::make($validated['password']);
      }

      $user->update($validated);

      return response()->json($user, 200);
    } catch (\Exception $e) {
      Log::error('Error updating user: ' . $e->getMessage());
      return response()->json(['message' => 'Error updating user'], 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy($id)
  {
    try {
      $user = User::find($id);

      if (!$user) {
        return response()->json([
          'message' => 'User not found.'
        ], 404);
      }

      $user->delete();

      return response()->noContent(); // 204 No Content
    } catch (\Exception $e) {
      Log::error('Error deleting user: ' . $e->getMessage());
      return response()->json([
        'message' => 'Error deleting user.',
        'error' => $e->getMessage()
      ], 500);
    }
  }
  // Validar o token
public function validateToken(Request $request)
{
    try {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json(['valid' => true, 'user' => $user], 200);
    } catch (\Exception $e) {
        return response()->json(['valid' => false, 'error' => $e->getMessage()], 401);
    }
}
}
