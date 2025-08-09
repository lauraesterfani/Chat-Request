<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// --- Rotas Públicas (sem autenticação) ---
Route::get('/', function () {
    return response()->json(["api" => "Ativa"]);
});

Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

// --- Rotas Protegidas (com autenticação JWT) ---
// Todas as rotas abaixo requerem autenticação via JWT.
Route::middleware('auth:api')->group(function () {

    // Rotas de autenticação
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/change-enrollment/{enrollment}', [AuthController::class, 'changeEnrollment']);

    // --- Rotas de 'user' (protegidas por papel) ---
    // Apenas usuários com o papel 'admin' podem acessar.
    Route::group(['middleware' => ['role:admin']], function () { 
        Route::apiResource('user', UserController::class)->except('store');
    });

    // --- Rotas de 'enrollment' (protegidas por permissão) ---
    // Apenas quem tem a permissão 'gerenciar matriculas' pode acessar.
    Route::apiResource('enrollment', EnrollmentController::class)
        ->middleware('permission:gerenciar matriculas');

    // --- Rotas de 'request' (protegidas por permissão) ---
    Route::get('request', [RequestController::class, 'index'])
        ->middleware('permission:listar requisicoes');
    Route::post('request', [RequestController::class, 'store'])
        ->middleware('permission:criar requisicao');
    Route::get('request/{request}', [RequestController::class, 'show'])
        ->middleware('permission:ver requisicao');
    Route::put('request/{request}', [RequestController::class, 'update'])
        ->middleware('permission:editar requisicao');
    Route::delete('request/{request}', [RequestController::class, 'destroy'])
        ->middleware('permission:deletar requisicao');

});
