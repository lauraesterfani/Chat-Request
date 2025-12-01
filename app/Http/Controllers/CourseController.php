<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course; // 💡 Importa o modelo Course
use Illuminate\Http\JsonResponse;

/**
 * Controller responsável por gerenciar operações relacionadas a Cursos.
 * Esta rota é pública (/api/courses) e é usada pelo frontend na tela de cadastro
 * para carregar as opções do dropdown.
 */
class CourseController extends Controller
{
    /**
     * Lista todos os cursos disponíveis.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // 🚨 CHAME A FUNÇÃO DE BUSCA REAL NO BANCO DE DADOS
        
        // 1. Usa o método all() para buscar todos os registros na tabela 'courses'.
        // 2. O select(['id', 'name']) garante que apenas os campos necessários
        //    (id e name) sejam retornados, otimizando a resposta para o frontend.
        
        // Garante que o ID e o Nome sejam retornados, essenciais para o dropdown do React.
        $courses = Course::all(['id', 'name']);
        
        // 3. Retorna a coleção de cursos como uma resposta JSON.
        // O frontend espera este formato: [{"id": "1", "name": "Engenharia de Software"}, ...]
        return response()->json($courses);
    }

    // Você pode adicionar outros métodos (store, update, destroy) aqui para a gestão de cursos,
    // mas o index é suficiente para o frontend de cadastro.
}