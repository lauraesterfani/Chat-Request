<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    try {
      $enrollments = Enrollment::all();
      return response()->json($enrollments, 200);
    } catch (\Exception $e) {
      Log::error('Error fetching enrollments: ' . $e->getMessage());
      return response()->json(['msg' => 'Error fetching enrollments'], 500);
    }
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    try {

      $validated = $request->validate([
        'enrollment' => 'required|string|max:255',
        'status' => 'required|in:active,locked,finished',
      ]);

      $enrollment = new Enrollment($validated);
      $enrollment->user_id = Auth::user()->id;
      $enrollment->save();

      return response()->json($enrollment, 201);
    } catch (\Exception $e) {
      Log::error('Error creating enrollments: ' . $e->getMessage());
      return response()->json(['msg' => 'Error creating enrollments'], 500);
    }
  }

  /**
   * Display the specified resource.
   */
  public function show($id)
  {
    try {
      $enrollment = Enrollment::find($id);

      if (!$enrollment) {
        return response()->json(['msg' => 'Enrollment not found.'], 404);
      }

      return response()->json($enrollment, 200);
    } catch (\Exception $e) {
      Log::error('Error fetching enrollment: ' . $e->getMessage());
      return response()->json(['msg' => 'Error fetching enrollment'], 500);
    }
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, $id)
  {
    try {

      $enrollment = Enrollment::findOrFail($id);

      $data = $request->validate([
        'enrollment' => 'sometimes|required|string|max:255',
        'status' => 'sometimes|required|in:active,locked,finished',
        'user_id' => 'sometimes|required|exists:users,id',
      ]);

      $enrollment->update($data);

      return response()->json($enrollment);
    } catch (\Exception $e) {
      Log::error('Error updating enrollment: ' . $e->getMessage());
      return response()->json(['msg' => 'Error updating enrollment'], 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy($id)
  {
    try {
      $enrollment = Enrollment::find($id);

      if (!$enrollment) {
        return response()->json(['msg' => 'Enrollment not found.'], 404);
      }

      $enrollment->delete();

      return response()->json(null, 204);
    } catch (\Exception $e) {
      Log::error('Error deleting enrollment: ' . $e->getMessage());
      return response()->json([
        'msg' => 'Error deleting enrollment.',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
