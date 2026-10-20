<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Models\User;
use Carbon\Carbon; // Necessário para os gráficos
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Retorna os Cards Principais (Visão Geral + Alertas)
     */
    public function index()
    {
        // Define o prazo de alerta (5 dias atrás)
        $prazoLimite = Carbon::now()->subDays(config('app.request_overdue_days', 5));

        $stats = [
            'total_requerimentos' => RequestModel::count(),
            'total_usuarios' => User::where('role', 'student')->count(),
            'pendentes' => RequestModel::where('status', 'pending')->count(),
            'em_analise' => RequestModel::where('status', 'analyzing')->count(),

            // Contagem de Atrasados (Não finalizados e antigos)
            'atrasados' => RequestModel::whereNotIn('status', ['completed', 'canceled'])
                ->where('created_at', '<', $prazoLimite)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Retorna dados para o Gráfico de Status (Pizza/Donut)
     */
    public function requerimentosPorStatus()
    {
        $statusCounts = RequestModel::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        return response()->json($statusCounts);
    }

    /**
     * Retorna dados para o Gráfico de Cursos (Barras)
     */
    public function requerimentosPorCurso()
    {
        // Pedidos novos usam o curso preservado no envio; legados mantêm o fallback.
        $courseCounts = RequestModel::join('users', 'requests.user_id', '=', 'users.id')
            ->join('courses', 'courses.id', '=', DB::raw('COALESCE(requests.course_id, users.course_id)'))
            ->select('courses.name as course_name', DB::raw('count(*) as total'))
            ->groupBy('courses.name')
            ->get();

        return response()->json($courseCounts);
    }
}
