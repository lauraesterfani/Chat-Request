<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rota pública para verificar se a API está ativa
Route::get('/', function () {
    return response()->json(["api" => "Ativa"]);
});

// Rotas públicas de autenticação e registro
// Qualquer pessoa pode criar um usuário ou fazer login.
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas (exigem autenticação com Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Rota para logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // AQUI: As rotas de requests foram movidas para cá.
    // Agora, qualquer usuário autenticado (staff, student ou admin) pode acessá-las.
    Route::apiResource('requests', ServiceRequestController::class);

    // Grupo de rotas protegidas APENAS para o admin
    // O middleware 'admin' verifica se o usuário autenticado tem o papel de admin.
    Route::middleware('admin')->group(function () {
        // Rota da dashboard, exclusiva para admins.
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Bem-vindo à dashboard de administrador!'], 200);
        });

        // Rotas de recursos de usuário e matrícula, exclusivas para admins.
        Route::apiResource('users', UserController::class)->except('store');
        Route::apiResource('enrollment', EnrollmentController::class);
    });

});
