<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

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
        'user' => [
          'id' => $user->id,
          'name' => $user->name,
          'email' => $user->email,
          'user_type' => $user->user_type
        ]
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
  public function show(string $id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    //
  }
}
