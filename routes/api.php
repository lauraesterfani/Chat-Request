<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\TypeRequestController;
use App\Http\Controllers\TypeDocumentsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\StaffAdminController;

/*
|--------------------------------------------------------------------------
| ROTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => response()->json(["api" => "Online", "status" => "OK"]));

Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/staff', [AuthController::class, 'loginStaff']);
Route::post('/validate-token', [EnrollmentController::class, 'validateToken']);

Route::get('/courses', [CourseController::class, 'index']); 
Route::get('/type-requests', [TypeRequestController::class, 'index']);

/*
|--------------------------------------------------------------------------
| ROTAS AUTENTICADAS (Geral)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api,staff_admins')->group(function () {
    
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    Route::get('/requests', [RequestController::class, 'index']); 
    Route::get('/my-requests', [RequestController::class, 'index']); 
    
    Route::post('/messages', [MessageController::class, 'store']);
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    
    Route::post('/requests', [RequestController::class, 'store']);
    Route::post('/change-enrollment/{id}', [AuthController::class, 'changeEnrollment']);

    Route::get('/staffs', [StaffController::class, 'index']); 
});

/*
|--------------------------------------------------------------------------
| ROTAS EXCLUSIVAS DE T.I. (STAFF)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:staff_admins', 'role:staff'])->group(function () {
    
    // Agrupado corretamente: GET para listar, POST para criar
    Route::get('/staff-admins', [StaffAdminController::class, 'index']);
    Route::post('/staff-admins', [StaffAdminController::class, 'store']);
    
    // Outras rotas de gestão
    Route::post('/staffs', [StaffController::class, 'store']);
    Route::delete('/staffs/{id}', [StaffController::class, 'destroy']);
    
    Route::post('/type-requests', [TypeRequestController::class, 'store']);
    Route::put('/type-requests/{id}', [TypeRequestController::class, 'update']);
    Route::delete('/type-requests/{id}', [TypeRequestController::class, 'destroy']);
});
/*
|--------------------------------------------------------------------------
| ROTAS EXCLUSIVAS DA CRADT (ADMIN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:staff_admins', 'role:admin'])->group(function () {
    
    Route::get('/requests/{id}', [RequestController::class, 'show']);
    Route::put('/requests/{id}', [RequestController::class, 'update']);
    
    Route::get('/dashboard/requerimentos', [DashboardController::class, 'index']);
    Route::get('/dashboard/estatisticas', [DashboardController::class, 'index']); 
    Route::get('/dashboard/graficos/status', [DashboardController::class, 'requerimentosPorStatus']); 
    Route::get('/dashboard/graficos/cursos', [DashboardController::class, 'requerimentosPorCurso']); 
});