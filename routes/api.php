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
Route::post('/login-adm', [AdminAuthController::class, 'login']);
Route::get('/requerimentos', [RequerimentoController::class, 'index']);

// --- ROTAS PROTEGIDAS PARA ALUNOS (exigem token de aluno) ---
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/validate-token', [AuthController::class, 'validateToken']);
    Route::get('/my-registrations', [MatriculaController::class, 'myRegistrations']);
    Route::get('/requerimentos/{id}', [RequerimentoController::class, 'show']);
    Route::apiResource('alunos', AlunoController::class);
    Route::apiResource('matriculas', MatriculaController::class);
    Route::apiResource('cursos', CursoController::class);
    Route::apiResource('campus', CampusController::class);
    Route::apiResource('tipos-anexo', TipoAnexoController::class);
    Route::apiResource('tipos-requerimento', TipoRequerimentoController::class);
});

