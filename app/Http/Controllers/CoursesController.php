<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CoursesController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    try {
      $courses = Course::all();
      return response()->json($courses, 200);
    } catch (\Exception $e) {
      Log::error('Error fetching courses: ' . $e->getMessage());
      return response()->json(['msg' => 'Error fetching courses'], 500);
    }
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request) {}

  /**
   * Display the specified resource.
   */
  public function show(Course $courses)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Course $courses)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Course $courses)
  {
    //
  }
}
