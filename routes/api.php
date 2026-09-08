<?php

use App\Http\Controllers\AuditRecordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\FormSchemaController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ResponseTemplateController;
use App\Http\Controllers\SlaPolicyController;
use App\Http\Controllers\StaffAccessScopeController;
use App\Http\Controllers\StaffAdminController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TypeRequestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ROTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => response()->json(['api' => 'Online', 'status' => 'OK']));

Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/staff', [AuthController::class, 'loginStaff']);
Route::post('/validate-token', [EnrollmentController::class, 'validateToken']);

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/type-requests', [TypeRequestController::class, 'index']);
Route::get('/type-requests/{typeId}/form', [FormSchemaController::class, 'published']);

/*
|--------------------------------------------------------------------------
| ROTAS AUTENTICADAS (Geral)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api,staff_admins')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::get('/requests', [RequestController::class, 'index']);
    Route::get('/my-requests', [RequestController::class, 'index']);

    Route::post('/documents/upload', [DocumentController::class, 'upload']);

    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/drafts', [FormSchemaController::class, 'drafts']);
    Route::post('/drafts', [FormSchemaController::class, 'saveDraft']);
    Route::put('/drafts/{draft}', [FormSchemaController::class, 'saveDraft']);
    Route::delete('/drafts/{draft}', [FormSchemaController::class, 'discard']);
    Route::post('/change-enrollment/{id}', [AuthController::class, 'changeEnrollment']);

    Route::get('/staffs', [StaffController::class, 'index']);
});

Route::middleware(['auth:staff_admins'])->get('/admins', [StaffAdminController::class, 'admins']);

/*
|--------------------------------------------------------------------------
| Templates disponíveis no atendimento
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api,staff_admins', 'role:admin,staff,cradt'])->group(function () {
    Route::get('/response-templates/active', [ResponseTemplateController::class, 'active']);
    Route::get('/admin/requests', [RequestController::class, 'index'])
        ->middleware('role:admin,cradt');
});

Route::middleware(['auth:staff_admins', 'role:admin,cradt'])->group(function () {
    Route::get('/audit-records', [AuditRecordController::class, 'index']);
    Route::get('/staff-access-scopes', [StaffAccessScopeController::class, 'index']);
    Route::post('/staff-access-scopes', [StaffAccessScopeController::class, 'store']);
    Route::delete('/staff-access-scopes/{scope}', [StaffAccessScopeController::class, 'destroy']);
    Route::post('/type-requests/{typeId}/form-versions', [FormSchemaController::class, 'store']);
    Route::post('/form-versions/{schemaVersion}/publish', [FormSchemaController::class, 'publish']);
    Route::get('/admin/queue', [RequestController::class, 'queue']);
    Route::post('/requests/{id}/assign', [RequestController::class, 'assign']);
    Route::get('/sla-policies', [SlaPolicyController::class, 'index']);
    Route::post('/sla-policies', [SlaPolicyController::class, 'store']);
    Route::post('/sla-policies/{slaPolicy}/activate', [SlaPolicyController::class, 'activate']);
});

/*
|--------------------------------------------------------------------------
| ROTAS EXCLUSIVAS DE T.I. (STAFF)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api,staff_admins', 'role:staff'])->group(function () {
    Route::get('/staff-admins', [StaffAdminController::class, 'index']);
    Route::post('/staff-admins', [StaffAdminController::class, 'store']);

    Route::post('/staffs', [StaffController::class, 'store']);
    Route::delete('/staffs/{id}', [StaffController::class, 'destroy']);

    Route::post('/type-requests', [TypeRequestController::class, 'store']);
    Route::put('/type-requests/{id}', [TypeRequestController::class, 'update']);
    Route::delete('/type-requests/{id}', [TypeRequestController::class, 'destroy']);

    Route::delete('/staff-admins/{id}', [StaffAdminController::class, 'destroy']);

});

Route::middleware(['auth:api,staff_admins', 'role:admin,staff,cradt'])->group(function () {
    Route::get('/response-templates', [ResponseTemplateController::class, 'index']);
    Route::get('/response-templates/{responseTemplate}', [ResponseTemplateController::class, 'show']);
    Route::post('/response-templates', [ResponseTemplateController::class, 'store']);
    Route::put('/response-templates/{responseTemplate}', [ResponseTemplateController::class, 'update']);
    Route::patch('/response-templates/{responseTemplate}/status', [ResponseTemplateController::class, 'updateStatus']);
});

/*
|--------------------------------------------------------------------------
| ROTAS EXCLUSIVAS DA CRADT (ADMIN)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api,staff_admins')->get('/requests/{id}', [RequestController::class, 'show']);
Route::middleware('auth:api,staff_admins')->get('/requests/{id}/events', [RequestController::class, 'events']);

Route::middleware('auth:api,staff_admins')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/count', [NotificationController::class, 'count']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::get('/notification-preferences', [NotificationController::class, 'preferences']);
    Route::put('/notification-preferences', [NotificationController::class, 'preferences']);
});

Route::middleware('auth:api,staff_admins')->prefix('requests/{requestId}/messages')->group(function () {
    Route::get('/', [MessageController::class, 'index']);
    Route::post('/', [MessageController::class, 'store']);
    Route::post('/read', [MessageController::class, 'markRead']);
});

Route::middleware(['auth:api,staff_admins', 'role:admin,cradt'])->group(function () {
    Route::put('/requests/{id}', [RequestController::class, 'update']);
    Route::post('/requests/{id}/forward', [RequestController::class, 'forward']);
    Route::post('/requests/{id}/return', [RequestController::class, 'returnToPrevious']);
    Route::post('/requests/{id}/decision', [RequestController::class, 'decision']);

    Route::get('/dashboard/requerimentos', [DashboardController::class, 'index']);
    Route::get('/dashboard/estatisticas', [DashboardController::class, 'index']);
    Route::get('/dashboard/graficos/status', [DashboardController::class, 'requerimentosPorStatus']);
    Route::get('/dashboard/graficos/cursos', [DashboardController::class, 'requerimentosPorCurso']);
});

/*
|--------------------------------------------------------------------------
| ROTAS DE REDEFINIÇÃO DE SENHA (Primeiro acesso)
|--------------------------------------------------------------------------
*/
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('auth:staff_admins');
