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
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rota pública para verificar se a API está ativa
Route::get('/', function () {
    return response()->json(["api" => "Ativa"]);
});

// Rotas públicas de autenticação e registro
// Qualquer pessoa pode criar um usuário ou fazer login.
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-adm', [AdminAuthController::class, 'login']);
Route::get('/requerimentos', [RequerimentoController::class, 'index']);

// Rotas protegidas para usuários autenticados via Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Rota para logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rotas de requests para usuários autenticados
    Route::apiResource('requests', ServiceRequestController::class);

    // Rotas exclusivas para admin
    Route::middleware('admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Bem-vindo à dashboard de administrador!'], 200);
        });

        Route::apiResource('users', UserController::class)->except('store');
        Route::apiResource('matriculas', MatriculaController::class);
    });
});

// Rotas protegidas para alunos autenticados via JWT (auth:api)
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
