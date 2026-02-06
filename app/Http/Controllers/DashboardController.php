<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request as HttpRequest;
use App\Models\Request as Requerimento;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Lista principal com filtros e paginação
     */
    public function index(HttpRequest $request)
    {
        // 1. Carrega relacionamentos: 'user' (do Request.php) e 'course' (do User.php)
        $query = Requerimento::with(['user.course', 'type']); 

        // --- FILTROS ---

        // Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Nome do Aluno
        if ($request->filled('nome')) {
            $nome = $request->input('nome');
            $query->whereHas('user', function($q) use ($nome) {
                $q->where('name', 'like', "%{$nome}%");
            });
        }

        // Matrícula (verifica enrollment_number ou matricula antiga)
        if ($request->filled('matricula')) {
            $matricula = $request->input('matricula');
            $query->whereHas('user', function($q) use ($matricula) {
                $q->where('enrollment_number', 'like', "%{$matricula}%")
                  ->orWhere('matricula', 'like', "%{$matricula}%");
            });
        }

        // Curso
        if ($request->filled('course_id')) {
            $courseId = $request->input('course_id');
            $query->whereHas('user', function($q) use ($courseId) {
                 $q->where('course_id', $courseId); 
            });
        }

        // Data de Abertura
        if ($request->filled('data_inicio')) {
            $query->whereBetween('created_at', [
                $request->input('data_inicio'), 
                $request->input('data_fim') ?? now()
            ]);
        }

        // Busca paginada
        $requerimentos = $query->latest('created_at')->paginate(15); 

        return response()->json([
            'requerimentos' => $requerimentos,
            'alertas' => [
                'vencidos_total' => Requerimento::where('status', 'pending')->count(),
            ],
        ]);
    }
    
    /**
     * Estatísticas para os Gráficos (Mantido do original)
     */
    public function requerimentosPorStatus()
    {
        $statusCounts = Requerimento::select('status', DB::raw('count(*) as total'))
                                    ->groupBy('status')
                                    ->get();

        return response()->json($statusCounts, 200);
    }

    public function requerimentosPorCurso()
    {
        // Busca contagem agrupada por user.course_id
        // (Adaptado para a nova estrutura onde o curso está no User)
        $courseCounts = Requerimento::join('users', 'requests.user_id', '=', 'users.id')
                                    ->select('users.course_id', DB::raw('count(*) as total'))
                                    ->whereNotNull('users.course_id')
                                    ->groupBy('users.course_id')
                                    ->get();
        
        // Mapeia IDs para Nomes (usando a constante do Model Enrollment ou buscando do banco)
        // Se você tiver uma tabela 'courses', seria melhor fazer join com ela. 
        // Aqui mantive a lógica de constante que você usava antes para compatibilidade.
        $result = $courseCounts->map(function ($item) {
            $courseName = Enrollment::FIXED_COURSES[$item->course_id] ?? 'Curso Desconhecido';
            return [
                'course_id' => $item->course_id,
                'course_name' => $courseName,
                'total' => (int) $item->total,
            ];
        });

        return response()->json($result, 200);
    }
    
    public function estatisticasGerais()
    {
        $totalRequerimentos = Requerimento::count();
        $totalUsuarios = \App\Models\User::count(); 

        return response()->json([
            'total_requerimentos' => $totalRequerimentos,
            'total_usuarios' => $totalUsuarios,
        ], 200);
    }
}