<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\RequestTypeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return response()->json(["api" => "Ativa"]);
});



Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-token', [EnrollmentController::class, 'validateToken']);
Route::get('/list-users', [UserController::class, 'index']);
Route::get('/list-enrollments', [EnrollmentController::class, 'index']);
Route::get('/list-requests', [RequestController::class, 'index']);
Route::get('/list-courses', [CoursesController::class, 'index']);

Route::middleware('auth:api')->group(function () {
  Route::get('/me', [AuthController::class, 'me']);
  Route::get('/my-enrollments', [UserController::class, 'myEnrollments']);
  Route::post('/refresh', [AuthController::class, 'refresh']);
  Route::post('/change-enrollment/{id}', [AuthController::class, 'setEnrollment']);

  Route::apiResource('user', UserController::class)->except('store');
  Route::apiResource('enrollment', EnrollmentController::class);
  // Route::apiResource('request', RequestController::class);

});
