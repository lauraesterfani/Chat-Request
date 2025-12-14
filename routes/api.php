<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\TypeRequestController; // Nome no SINGULAR (Corrigido)
use App\Http\Controllers\TypeDocumentsController; // Verifique se o seu arquivo é Singular ou Plural (mantive Plural se você não alterou)

/*
|--------------------------------------------------------------------------
| ROTAS PÚBLICAS (Acesso LIVRE - Sem Login)
|--------------------------------------------------------------------------
| Essenciais para o Chatbot (ler opções) e para o Cadastro (ler cursos).
*/

// Teste de API
Route::get('/', function () {
    return response()->json(["api" => "Online", "status" => "OK"]);
});

// Autenticação e Cadastro
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-token', [EnrollmentController::class, 'validateToken']);

// LISTAS PÚBLICAS (Correção para o erro "Unauthenticated" no Chat e Cadastro)
Route::get('/courses', [CourseController::class, 'index']); 
Route::get('/type-requests', [TypeRequestController::class, 'index']); 


/*
|--------------------------------------------------------------------------
| ROTAS PROTEGIDAS (Necessitam de Token)
|--------------------------------------------------------------------------
| A partir daqui, tudo exige o cabeçalho "Authorization: Bearer <token>"
*/

// GRUPO 1: Usuários Logados (Alunos, Staff, Admin)
Route::middleware('auth:api')->group(function () {
    
    // Dados do Usuário
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // Atualizar Matrícula
    Route::post('/change-enrollment/{id}', [AuthController::class, 'changeEnrollment']);

    // Uploads e Mensagens
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::post('/messages', [MessageController::class, 'store']);
    
    // Requerimentos (CRUD Básico para Alunos)
    // A listagem é separada para permitir filtros customizados se necessário
    Route::get('/requests', [RequestController::class, 'index']); 
    Route::apiResource('requests', RequestController::class)->except(['index']);
});


// GRUPO 2: Gestão (Staff e Admin)
Route::middleware(['auth:api', 'role:admin,staff'])->group(function () {
    
    // Gestão de Matrículas
    Route::apiResource('enrollment', EnrollmentController::class)->except(['destroy']);
    
    // Dashboard (Gráficos e Relatórios)
    Route::prefix('dashboard')->group(function () {
        Route::get('/requerimentos', [DashboardController::class, 'index']);
        Route::get('/status', [DashboardController::class, 'requerimentosPorStatus']); 
        Route::get('/cursos', [DashboardController::class, 'requerimentosPorCurso']); 
        Route::get('/estatisticas', [DashboardController::class, 'estatisticasGerais']); 
    });
});


// GRUPO 3: Administração Total (Só Admin)
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    
    // Gestão de Usuários
    Route::apiResource('user', UserController::class)->except('store');
    
    // Gestão de Tipos (Create, Update, Delete)
    // O 'except index' evita conflito com a rota pública lá de cima
    Route::apiResource('type-requests', TypeRequestController::class)->except(['index']);
    
    // Se o controller de documentos for TypeDocumentsController:
    Route::apiResource('type-documents', TypeDocumentsController::class); 
    
    // Rota Especial: Vincular documentos a requerimentos
    Route::post('/type-requests/{id}/sync-documents', [TypeRequestController::class, 'syncDocumentTypes']);

    // Deletar Matrícula
    Route::delete('enrollment/{enrollment}', [EnrollmentController::class, 'destroy']);
});