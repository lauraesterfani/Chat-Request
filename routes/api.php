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

/*
|--------------------------------------------------------------------------
| ROTAS PÚBLICAS (Sem Login)
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => response()->json(["api" => "Online", "status" => "OK"]));
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/staff', [AuthController::class, 'loginStaff']);
Route::post('/validate-token', [EnrollmentController::class, 'validateToken']);
Route::get('/courses', [CourseController::class, 'index']); 

/*
|--------------------------------------------------------------------------
| ROTAS AUTENTICADAS (Todos Logados: Aluno, Staff, Admin)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {
    // Dados do Usuário
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // Aluno: Atualizações
    Route::post('/change-enrollment/{id}', [AuthController::class, 'changeEnrollment']);
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::post('/messages', [MessageController::class, 'store']);
    
    // Aluno: Criar e Ver seus Pedidos
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/my-requests', [RequestController::class, 'index']); 
    
    // --- LISTAS GERAIS (Correção do Erro 403/404) ---
    // Todos precisam VER os tipos para criar pedidos ou gerenciar
    Route::get('/type-requests', [TypeRequestController::class, 'index']);
    
    // Permitir ver a equipe (nomes/emails) para saber quem contatar
    // Isso resolve o 404 do Staff e do Admin
    Route::get('/staffs', [StaffController::class, 'index']); 
});

/*
|--------------------------------------------------------------------------
| ROTAS EXCLUSIVAS DE T.I. (STAFF)
|--------------------------------------------------------------------------
| Poder Supremo: Criar e Excluir Configurações
*/
Route::middleware(['auth:api', 'role:staff'])->group(function () {
    // Gestão de Equipe (Só TI cria/deleta)
    Route::post('/staffs', [StaffController::class, 'store']);
    Route::delete('/staffs/{id}', [StaffController::class, 'destroy']);
    
    // Gestão de Tipos (Só TI cria/deleta)
    Route::post('/type-requests', [TypeRequestController::class, 'store']);
    Route::put('/type-requests/{id}', [TypeRequestController::class, 'update']);
    Route::delete('/type-requests/{id}', [TypeRequestController::class, 'destroy']);
    
    // Outras configs sensíveis
    Route::apiResource('type-documents', TypeDocumentsController::class); 
    Route::delete('enrollment/{enrollment}', [EnrollmentController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| ROTAS EXCLUSIVAS DA CRADT (ADMIN)
|--------------------------------------------------------------------------
| Operacional: Ver todos os pedidos e Dashboard
*/
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Ver e Responder Requerimentos
    Route::get('/requests', [RequestController::class, 'index']); 
    Route::get('/requests/{id}', [RequestController::class, 'show']);
    Route::put('/requests/{id}', [RequestController::class, 'update']);
    
    // Dashboard
    Route::get('/dashboard/requerimentos', [DashboardController::class, 'index']);
    Route::get('/dashboard/estatisticas', [DashboardController::class, 'index']); 
    Route::get('/dashboard/graficos/status', [DashboardController::class, 'requerimentosPorStatus']); 
    Route::get('/dashboard/graficos/cursos', [DashboardController::class, 'requerimentosPorCurso']); 
});