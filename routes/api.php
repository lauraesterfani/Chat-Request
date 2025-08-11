<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CursoController;
use App\Http\Controllers\Api\V1\CampusController;
use App\Http\Controllers\Api\V1\TipoAnexoController;
use App\Http\Controllers\Api\V1\TipoRequerimentoController;
use App\Http\Controllers\Api\V1\AlunoController;
use App\Http\Controllers\Api\V1\MatriculaController;
use App\Http\Controllers\Api\V1\RequerimentoController;
use App\Http\Controllers\Api\V1\AuthController; // Importe o novo controller

Route::prefix('v1')->group(function () {
    // --- ROTAS PÚBLICAS ---

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    Route::get('/matriculas', [MatriculaController::class, 'index']);

    // --- ROTAS PROTEGIDAS ---
    // Todas as rotas dentro deste grupo exigirão um token válido.
    // Alterado de 'sanctum' para 'api' para corresponder ao driver JWT.
    Route::middleware('auth:api')->group(function () {
        Route::post('/validate-token', [AuthController::class, 'validateToken']);

        //Matriculas
        // Route::get('/matriculas', [MatriculaController::class, 'index']);


        // Rota para fazer logout
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Seus recursos de CRUD existentes agora estão protegidos
        Route::apiResource('cursos', CursoController::class);
        Route::apiResource('campus', CampusController::class);
        Route::apiResource('tipos-anexo', TipoAnexoController::class);
        Route::apiResource('tipos-requerimento', TipoRequerimentoController::class);
        Route::apiResource('alunos', AlunoController::class);
        Route::apiResource('matriculas', MatriculaController::class);
        Route::apiResource('requerimentos', RequerimentoController::class);
    });
});
