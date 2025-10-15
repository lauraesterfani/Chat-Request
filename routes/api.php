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
// O middleware 'auth:api' garante que o usuário está logado.
Route::middleware('auth:api')->group(function () {
    // --- Rotas de Autenticação e Usuário Existentes ---
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // Alunos e Funcionários precisam poder mudar suas próprias informações
    Route::post('/change-enrollment/{id}', [AuthController::class, 'changeEnrollment']);

    // --- Rotas de Comunicação e Uso Comum ---
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::post('/messages', [MessageController::class, 'store']);
    
    // Rotas de Requerimento:
    // 1. Definição da rota de Listagem (index) separadamente.
    // Isso garante que o index seja executado sem o bloqueio automático de policy
    // que estava sendo aplicado pelo Route::apiResource.
    Route::get('/requests', [RequestController::class, 'index']); 

    // 2. O restante do recurso (store, show, update, destroy) usa o Policy padrão.
    Route::apiResource('requests', RequestController::class)->except(['index']);
});


// ----------------------------------------------------------------------
// --- GRUPO 2: ROTAS DE GESTÃO (Acessível por Admin e Staff)
// ----------------------------------------------------------------------
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
