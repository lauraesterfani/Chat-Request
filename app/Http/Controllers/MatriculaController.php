<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Matricula; // Importe o Model
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Matricula::with(['aluno', 'curso', 'campus'])->paginate(15);
    }

    /**
     * Retorna as matrículas associadas ao usuário autenticado.
     */
    public function myRegistrations(Request $request)
    {
        // Pega o ID do usuário (Aluno) que está autenticado
        $alunoId = auth('api')->id();

        // Se não houver um ID, retorna uma lista vazia para segurança
        if (!$alunoId) {
            return response()->json([]);
        }

        // FAZ A CONSULTA MANUALMENTE:
        // Busca na tabela 'matriculas' onde o 'id_aluno' é igual ao ID do usuário logado.
        $matriculas = Matricula::where('id_aluno', $alunoId)
                                ->with(['curso', 'campus']) // Carrega os dados relacionados
                                ->get();

        // Retorna a lista de matrículas em formato JSON
        return response()->json($matriculas);
    }

    /**
     * Display the specified resource.
     */
    public function show(Matricula $matricula)
    {
        return $matricula->load(['aluno', 'curso', 'campus']);
    }

    // ... outros métodos (store, update, destroy) ...
}