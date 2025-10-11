<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnrollmentController extends Controller
{
    public function index()
    {
        try {
            $enrollments = Enrollment::with(['user'])->get(); 
            return response()->json($enrollments, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching enrollments: ' . $e->getMessage());
            return response()->json(['msg' => 'Error fetching enrollments'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'enrollment' => 'required|string|unique:enrollments,enrollment|max:31',
                'status' => 'required|in:active,locked,finished',
                'user_id' => 'required|uuid|exists:users,id',
                'course_id' => 'required|uuid', 
            ]);

            $validated['id'] = (string) Str::uuid();

            $enrollment = Enrollment::create($validated);

            return response()->json($enrollment, 201);
        } catch (\Exception $e) {
            Log::error('Error creating enrollments: ' . $e->getMessage());
            return response()->json(['msg' => 'Error creating enrollments'], 500);
        }
    }

    public function show($id)
    {
        try {
            $enrollment = Enrollment::with(['user'])->find($id); 

            if (!$enrollment) {
                return response()->json(['msg' => 'Enrollment not found.'], 404);
            }

            return response()->json($enrollment, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching enrollment: ' . $e->getMessage());
            return response()->json(['msg' => 'Error fetching enrollment'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $enrollment = Enrollment::findOrFail($id);

            $data = $request->validate([
                'enrollment' => 'sometimes|required|string|max:255',
                'status' => 'sometimes|required|in:active,locked,finished',
                'user_id' => 'sometimes|required|uuid|exists:users,id',
                'course_id' => 'sometimes|required|uuid', 
            ]);

            $enrollment->update($data);

            return response()->json($enrollment);
        } catch (\Exception $e) {
            Log::error('Error updating enrollment: ' . $e->getMessage());
            return response()->json(['msg' => 'Error updating enrollment'], 500);
        }
    }

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