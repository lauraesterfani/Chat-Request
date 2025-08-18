<?php

use App\Http\Controllers\AdminAuthController;
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
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-adm', [AdminAuthController::class, 'loginWithEmail']);
Route::post('/validate-token', [AuthController::class, 'validateToken']);


// --- ROTAS PRIVADA COM AUTENTICAÇÃO NECESSÁRIA ---
Route::middleware('auth:api')->group(function () {

    //Matriculas
    Route::get('/requerimentos/{id}', [RequerimentoController::class, 'show']);
    Route::get('/my-requerimentos', [RequerimentoController::class, 'myRequerimentos']);
    Route::get('/requerimentos', [RequerimentoController::class, 'index']);
    Route::post('/requerimentos', [RequerimentoController::class, 'store']);
    Route::patch('/requerimentos/{requerimento}/status', [RequerimentoController::class, 'updateStatus']);

    //Requerimentos
    Route::get('/alunos/{aluno}/requerimentos', [RequerimentoController::class, 'listByAluno']);
    Route::apiResource('alunos', AlunoController::class);

    //Rotas de autenticação
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    //Rotas de administração
    Route::get('/my-registrations', [MatriculaController::class, 'myRegistrations']);
    Route::apiResource('matriculas', MatriculaController::class);

    // Demais recursos
    Route::apiResource('cursos', CursoController::class);
    Route::apiResource('campus', CampusController::class);
    Route::apiResource('tipos-anexo', TipoAnexoController::class);
    Route::apiResource('tipos-requerimento', TipoRequerimentoController::class);
});

