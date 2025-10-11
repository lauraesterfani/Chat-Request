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

Route::get('/', function () {
    return response()->json(["api" => "Ativa"]);
});

Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-token', [EnrollmentController::class, 'validateToken']);

Route::middleware('auth:api')->group(function () {
    // --- Rotas de Autenticação e Usuário Existentes ---
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/change-enrollment/{id}', [AuthController::class, 'changeEnrollment']);

    // --- Rotas de Recursos (CRUD) ---
    Route::apiResource('user', UserController::class)->except('store');
    Route::apiResource('enrollment', EnrollmentController::class);
    Route::apiResource('type_requests', TypeRequestsController::class);
    Route::apiResource('type_documents', TypeDocumentsController::class);
    
    // Rota CRUD dos Requerimentos (Usa apiResource)
    Route::apiResource('requests', RequestController::class); 

    Route::post('/request-types/{id}/document-types', [TypeRequestsController::class, 'attachDocumentType']);

    // --- ROTAS DO DASHBOARD CRADT (Filtros, Alertas e Estatísticas) ---
    Route::prefix('dashboard')->group(function () {
        Route::get('/requerimentos', [DashboardController::class, 'index']);
        Route::get('/status', [DashboardController::class, 'requerimentosPorStatus']); 
        Route::get('/cursos', [DashboardController::class, 'requerimentosPorCurso']); 
        Route::get('/estatisticas', [DashboardController::class, 'estatisticasGerais']); 
    });

    // --- ROTAS DE CHAT E UPLOAD ---
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::post('/messages', [MessageController::class, 'store']);

});

