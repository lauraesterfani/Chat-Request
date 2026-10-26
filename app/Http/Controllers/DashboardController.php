<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Services\RequestAccessService;
use Carbon\Carbon; // Necessário para os gráficos
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Retorna os Cards Principais (Visão Geral + Alertas)
     */
    public function index(RequestAccessService $access)
    {
        // Define o prazo de alerta (5 dias atrás)
        $prazoLimite = Carbon::now()->subDays(config('app.request_overdue_days', 5));

        $actor = auth('staff_admins')->user();
        $visible = fn () => $access->constrainVisibleRequests(RequestModel::query(), $actor);
        $stats = [
            'total_requerimentos' => $visible()->count(),
            'total_usuarios' => $visible()->distinct()->count('user_id'),
            'pendentes' => $visible()->where('status', 'pending')->count(),
            'em_analise' => $visible()->where('status', 'analyzing')->count(),

            // Contagem de Atrasados (Não finalizados e antigos)
            'atrasados' => $visible()->whereNotIn('status', ['completed', 'canceled'])
                ->where('created_at', '<', $prazoLimite)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Retorna dados para o Gráfico de Status (Pizza/Donut)
     */
    public function requerimentosPorStatus(RequestAccessService $access)
    {
        $statusCounts = $access->constrainVisibleRequests(RequestModel::query(), auth('staff_admins')->user())
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        return response()->json($statusCounts);
    }

    /**
     * Retorna dados para o Gráfico de Cursos (Barras)
     */
    public function requerimentosPorCurso(RequestAccessService $access)
    {
        // Pedidos novos usam o curso preservado no envio; legados mantêm o fallback.
        $courseCounts = $access->constrainVisibleRequests(RequestModel::query(), auth('staff_admins')->user())
            ->join('users', 'requests.user_id', '=', 'users.id')
            ->join('courses', 'courses.id', '=', DB::raw('COALESCE(requests.course_id, users.course_id)'))
            ->select('courses.name as course_name', DB::raw('count(*) as total'))
            ->groupBy('courses.name')
            ->get();

        return response()->json($courseCounts);
    }
}
