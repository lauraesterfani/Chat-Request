<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\TypeDocumentsController;
use App\Http\Controllers\TypeRequestsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return response()->json(["api" => "Ativa"]);
});



Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-token', [EnrollmentController::class, 'validateToken']);

Route::middleware('auth:api')->group(function () {
  Route::get('/me', [AuthController::class, 'me']);
  Route::post('/logout', [AuthController::class, 'logout']);
  Route::post('/refresh', [AuthController::class, 'refresh']);
  Route::post('/change-enrollment/{id}', [AuthController::class, 'changeEnrollment']);

  Route::apiResource('user', UserController::class)->except('store');
  Route::apiResource('enrollment', EnrollmentController::class);
  Route::apiResource('type_requests', TypeRequestsController::class);
  Route::apiResource('type_documents', TypeDocumentsController::class);

  Route::post('/request-types/{id}/document-types', [TypeRequestsController::class, 'attachDocumentType']);
});
