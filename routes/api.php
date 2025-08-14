<?php

use App\Http\Controllers\AdminAuthController;

// Namespaces dos seus controllers
use App\Http\Controllers\AlunoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\RequerimentoController;
use App\Http\Controllers\TipoAnexoController;
use App\Http\Controllers\TipoRequerimentoController;
use Illuminate\Support\Facades\Route;

// --- ROTAS PÚBLICAS DE AUTENTICAÇÃO ---
// Acessíveis sem token
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-adm', [AdminAuthController::class, 'loginWithEmail']);
// Route::post('/login-adm', [AdminAuthController::class, 'login']);
Route::get('/requerimentos', [RequerimentoController::class, 'index']);
Route::post('/requerimentos', [RequerimentoController::class, 'store']);
Route::get('/alunos/{aluno}/requerimentos', [RequerimentoController::class, 'listByAluno']);
Route::get('/my-requerimentos', [RequerimentoController::class, 'myRequerimentos']);
// --- ROTAS PROTEGIDAS PARA ALUNOS (exigem token de aluno) ---
Route::middleware('auth:api')->group(function () {
    Route::get('/requerimentos/{id}', [RequerimentoController::class, 'show']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::patch('/requerimentos/{requerimento}/status', [RequerimentoController::class, 'updateStatus']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/validate-token', [AuthController::class, 'validateToken']);
    Route::get('/my-registrations', [MatriculaController::class, 'myRegistrations']);
    Route::apiResource('alunos', AlunoController::class);
    Route::apiResource('matriculas', MatriculaController::class);
    Route::apiResource('cursos', CursoController::class);
    Route::apiResource('campus', CampusController::class);
    Route::apiResource('tipos-anexo', TipoAnexoController::class);
    Route::apiResource('tipos-requerimento', TipoRequerimentoController::class);
});

// Route::apiResource('requerimentos', RequerimentoController::class);
