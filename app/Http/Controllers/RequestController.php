<?php

namespace App\Http\Controllers;

use App\Models\Request as ModelsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class RequestController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    try {
      $requests = ModelsRequest::all();
      return response()->json($requests, 200);
    } catch (\Exception $e) {
      Log::error('Error fetching requests: ' . $e->getMessage());
      return response()->json(['msg' => 'Error fetching requests'], 500);
    }
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    try {
      $validated = $request->validate([
        'protocol' => 'required|string|max:255',
        'status' => 'required|string|max:50',
        'observations' => 'nullable|string|max:1000',
      ]);

      $validated['id'] = (string) Str::uuid();

      $requests = new ModelsRequest($validated);

      // Pega o enrollment direto do token JWT
      $enrollment = JWTAuth::parseToken()->getPayload()->get('enrollment');

      if (!$enrollment) {
        return response()->json(['msg' => 'Enrollment not found in token'], 400);
      }

      $requests->enrollment = $enrollment;
      $requests->save();

      return response()->json($requests, 201);
    } catch (\Exception $e) {
      Log::error('Error creating requests: ' . $e->getMessage());
      return response()->json(['msg' => 'Error creating requests'], 500);
    }
  }


  /**
   * Display the specified resource.
   */
  public function show($id)
  {
    try {
      $request = ModelsRequest::find($id);

      if (!$request) {
        return response()->json(['msg' => 'Request not found'], 404);
      }

      return response()->json($request, 200);
    } catch (\Exception $e) {
      Log::error('Error fetching request: ' . $e->getMessage());
      return response()->json(['msg' => 'Error fetching request'], 500);
    }
  }


  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, $id)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy($id)
  {
    try {
      $request = ModelsRequest::find($id);

      if (!$request) {
        return response()->json(['msg' => 'Request not found'], 404);
      }

      $request->delete();

      return response()->json(['msg' => 'Request deleted successfully'], 200);
    } catch (\Exception $e) {
      Log::error('Error deleting request: ' . $e->getMessage());
      return response()->json(['msg' => 'Error deleting request'], 500);
    }
  }
}
