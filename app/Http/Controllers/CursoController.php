<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index()
    {
        return Curso::paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_curso' => 'required|string|max:150|unique:cursos',
            'modalidade' => 'required|in:Presencial,EAD,Híbrido',
        ]);

        $curso = Curso::create($validated);
        return response()->json($curso, 201);
    }

    public function show(Curso $curso)
    {
        return response()->json($curso);
    }

    public function update(Request $request, Curso $curso)
    {
        $validated = $request->validate([
            'nome_curso' => 'sometimes|required|string|max:150|unique:cursos,nome_curso,' . $curso->id_curso . ',id_curso',
            'modalidade' => 'sometimes|required|in:Presencial,EAD,Híbrido',
        ]);

        $curso->update($validated);
        return response()->json($curso);
    }

    public function destroy(Curso $curso)
    {
        $curso->delete();
        return response()->json(null, 204);
    }
}