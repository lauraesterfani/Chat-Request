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
use App\Http\Controllers\TypeRequestController;
use App\Http\Controllers\TypeDocumentsController;

/*
|--------------------------------------------------------------------------
| ROTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => response()->json(["api" => "Online", "status" => "OK"]));

// Autenticação e Cadastro
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/staff', [AuthController::class, 'loginStaff']);
Route::post('/validate-token', [EnrollmentController::class, 'validateToken']);

// Listas públicas
Route::get('/courses', [CourseController::class, 'index']); 
Route::get('/type-requests', [TypeRequestController::class, 'index']); 


/*
|--------------------------------------------------------------------------
| ROTAS PROTEGIDAS (auth:api)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {
    // Dados do usuário
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Atualizar matrícula
    Route::post('/change-enrollment/{id}', [AuthController::class, 'changeEnrollment']);

    // Uploads e mensagens
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::post('/messages', [MessageController::class, 'store']);

    // Requerimentos (alunos criam e veem os seus)
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests', [RequestController::class, 'index']); 
    Route::apiResource('requests', RequestController::class)->except(['index']);
});


/*
|--------------------------------------------------------------------------
| ROTAS DE STAFF
|--------------------------------------------------------------------------
| Apenas usuários com role=staff
*/
Route::middleware(['auth:api', 'role:staff'])->prefix('staff')->group(function () {
    // Gestão de admins
    Route::get('/admins', [UserController::class, 'listAdmins']);
    Route::post('/admins', [UserController::class, 'storeAdmin']);
});


/*
|--------------------------------------------------------------------------
| ROTAS DE ADMIN
|--------------------------------------------------------------------------
| Apenas usuários com role=admin
*/
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    // Ver todos os requerimentos
    Route::get('/requests', [RequestController::class, 'index']); 

    // Gestão de usuários
    Route::apiResource('user', UserController::class)->except('store');

    // Cadastro de staff
    Route::post('/staff/register', [UserController::class, 'storeStaff']);

    // Tipos de requerimento e documentos
    Route::apiResource('type-requests', TypeRequestController::class)->except(['index']);
    Route::apiResource('type-documents', TypeDocumentsController::class); 
    Route::post('/type-requests/{id}/sync-documents', [TypeRequestController::class, 'syncDocumentTypes']);

    // Deletar matrícula
    Route::delete('enrollment/{enrollment}', [EnrollmentController::class, 'destroy']);
});


/*
|--------------------------------------------------------------------------
| ROTAS DE GESTÃO (Admin + Staff)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:admin,staff'])->prefix('dashboard')->group(function () {
    Route::get('/requerimentos', [DashboardController::class, 'index']);
    Route::get('/status', [DashboardController::class, 'requerimentosPorStatus']); 
    Route::get('/cursos', [DashboardController::class, 'requerimentosPorCurso']); 
    Route::get('/estatisticas', [DashboardController::class, 'estatisticasGerais']); 
});
