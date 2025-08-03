<?php

namespace App\Http\Controllers;

use App\Models\Request as ModelsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
         'observations' => 'nullable|string|max:1000',""
      ]);
      $validated['id'] = (string) Str::uuid();

      $requests = new ModelsRequest($validated);
      $requests->enrollment = Auth::user()->enrollment;
      $requests->save();

      return response()->json($requests, 201);
    } catch (\Exception $e) {
      Log::error('Error creating enrollments: ' . $e->getMessage());
      return response()->json(['msg' => 'Error creating enrollments'], 500);
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(Request $request)
  {
    //
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
  public function destroy(Request $request)
  {
    //
  }
}
