<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return response()->json(["api" => "Ativa"]);
});



Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
  Route::get('/me', [AuthController::class, 'me']);
  Route::post('/logout', [AuthController::class, 'logout']);
  Route::post('/refresh', [AuthController::class, 'refresh']);
  Route::post('/change-enrollment/{enrollment}', [AuthController::class, 'changeEnrollment']);

  Route::apiResource('user', UserController::class)->except('store');
  Route::apiResource('enrollment', EnrollmentController::class);
  // Route::apiResource('request', RequestController::class);
  
});
