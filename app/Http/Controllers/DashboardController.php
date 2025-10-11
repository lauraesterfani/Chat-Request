<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request as HttpRequest; 
use App\Models\Request as Requerimento; 
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    public function index(HttpRequest $request)
    {
        // 1. Inicializa a Query
        $query = Requerimento::with(['aluno', 'enrollment.course', 'typeRequest']);

        // 2. Aplica Filtros Dinâmicos (Critérios de Aceitação)

        if ($request->filled('status')) {
            $query->situacao($request->input('status'));
        }

        
        if ($request->filled('aluno_id')) {
            $query->porAluno($request->input('aluno_id'));
        }

        
        if ($request->filled('course_id')) {
            $courseId = $request->input('course_id');
            $query->whereHas('enrollment', function($q) use ($courseId) {
                
                $q->where('course_id', $courseId); 
            });
        }
        
        
        if ($request->filled('data_inicio')) {
            $query->dataAbertura(
                $request->input('data_inicio'), 
                $request->input('data_fim') ?? null
            );
        }

       

        
        $vencidosCount = Requerimento::vencidos()->count();
        
        
        $requerimentos = $query->latest('created_at')->paginate(15); 

    
        return response()->json([
            'requerimentos' => $requerimentos,
            'alertas' => [
                'vencidos_total' => $vencidosCount,
            ],
        ]);
    }
    
    public function requerimentosPorStatus()
    {
        $statusCounts = Requerimento::select('status', DB::raw('count(*) as total'))
                                    ->groupBy('status')
                                    ->get();

        return response()->json($statusCounts, 200);
    }

    public function requerimentosPorCurso()
    {
        // 1. Obter a contagem agrupada pelo course_id na tabela 'enrollments'
        $courseCounts = Requerimento::join('enrollments', 'requests.enrollment_id', '=', 'enrollments.id')
                                    ->select('enrollments.course_id', DB::raw('count(*) as total'))
                                    ->groupBy('enrollments.course_id')
                                    ->get();
        
        // 2. Mapear o course_id para o nome do curso usando a constante FIXED_COURSES
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
    
    /**
     * Retorna o total de requerimentos e o total de usuários.
     * @return \Illuminate\Http\JsonResponse
     */
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