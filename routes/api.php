<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController; 
use App\Http\Controllers\DocumentController; 
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\MessageController; 
use App\Http\Controllers\RequestController;
use App\Http\Controllers\TypeDocumentsController;
use App\Http\Controllers\TypeRequestsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// --- ROTAS PÚBLICAS (Acesso sem autenticação) ---
Route::get('/', function () {
    return response()->json(["api" => "Ativa"]);
});

Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-token', [EnrollmentController::class, 'validateToken']);


// ----------------------------------------------------------------------
// --- GRUPO 1: ROTAS AUTENTICADAS (Acessível por Admin, Staff e Student)
// ----------------------------------------------------------------------
// ALTERADO: De 'auth:sanctum' para 'auth:api' para usar o JWT
Route::middleware('auth:api')->group(function () {
    // --- Rotas de Autenticação e Usuário Existentes ---
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // Alunos e Funcionários precisam poder mudar suas próprias informações
    Route::post('/change-enrollment/{id}', [AuthController::class, 'changeEnrollment']);

    // --- Rotas de Comunicação e Uso Comum ---
    // Todos os usuários podem enviar mensagens e fazer upload de documentos (do próprio usuário)
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::post('/messages', [MessageController::class, 'store']);
    
    // Rotas de Requerimento
    Route::apiResource('requests', RequestController::class); 
});


// ----------------------------------------------------------------------
// --- GRUPO 2: ROTAS DE GESTÃO (Acessível por Admin e Staff)
// ----------------------------------------------------------------------
// ALTERADO: De 'auth:sanctum' para 'auth:api' para usar o JWT
Route::middleware(['auth:api', 'role:admin,staff'])->group(function () {
    // Gestão de Matrículas (Enrollments)
    Route::apiResource('enrollment', EnrollmentController::class)->except(['destroy']);
    
    // Rotas do Dashboard (Estatísticas e Filtros)
    Route::prefix('dashboard')->group(function () {
        Route::get('/requerimentos', [DashboardController::class, 'index']);
        Route::get('/status', [DashboardController::class, 'requerimentosPorStatus']); 
        Route::get('/cursos', [DashboardController::class, 'requerimentosPorCurso']); 
        Route::get('/estatisticas', [DashboardController::class, 'estatisticasGerais']); 
    });
});


// ----------------------------------------------------------------------
// --- GRUPO 3: ROTAS ADMINISTRATIVAS (Exclusivo para Admin)
// ----------------------------------------------------------------------
// ALTERADO: De 'auth:sanctum' para 'auth:api' para usar o JWT
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Gestão de Usuários (CRUD completo, exceto o 'store' que é a rota pública de /register)
    Route::apiResource('user', UserController::class)->except('store');
    
    // Gestão dos Tipos de Documentos e Requerimentos
    Route::apiResource('type_requests', TypeRequestsController::class);
    Route::apiResource('type_documents', TypeDocumentsController::class);
    
    // Rota de vinculação de tipos de documentos a tipos de requerimentos
    Route::post('/request-types/{id}/document-types', [TypeRequestsController::class, 'attachDocumentType']);

    // O Admin pode deletar matrículas
    Route::delete('enrollment/{enrollment}', [EnrollmentController::class, 'destroy']);
});
