<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController; // Novo Import
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\MessageController; // Novo Import
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

    Route::apiResource('user', UserController::class)->except('store');
    Route::apiResource('enrollment', EnrollmentController::class);
    Route::apiResource('type_requests', TypeRequestsController::class);
    Route::apiResource('type_documents', TypeDocumentsController::class);

    Route::post('/request-types/{id}/document-types', [TypeRequestsController::class, 'attachDocumentType']);

    // --- NOVAS ROTAS PARA CHAT E UPLOAD ---
    
    // Etapa 1: Upload do Arquivo (salva o arquivo e retorna o document_id)
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    
    // Etapa 2: Envio da Mensagem (cria a mensagem, vincula o document_id e dispara o WebSocket)
    Route::post('/messages', [MessageController::class, 'store']);

});
