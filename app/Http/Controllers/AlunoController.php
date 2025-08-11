<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Aluno;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AlunoController extends Controller
{
    public function index(Request $request)
    {
        $query = Aluno::query();

        // Exemplo de busca simples por nome ou cpf
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nome_completo', 'like', "%{$searchTerm}%")
                    ->orWhere('cpf', 'like', "%{$searchTerm}%");
            });
        }

        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_completo'   => 'required|string|max:255',
            'email'           => 'required|email|max:255|unique:alunos',
            'cpf'             => 'required|string|max:11|unique:alunos',
            'telefone'        => 'nullable|string|max:20',
            'identidade'      => 'required|string|max:20',
            'orgao_expedidor' => 'required|string|max:20',
            'tipo_usuario'    => 'required|in:Estudante,CRADT,Admin',
        ]);

        $aluno = Aluno::create($validated);
        return response()->json($aluno, 201);
    }

    public function show(Aluno $aluno)
    {
        // Carrega as matrículas relacionadas a este aluno
        return $aluno->load('matriculas.curso', 'matriculas.campus');
    }

    // Métodos update() e destroy() seguem o padrão...
    public function update(Request $request, Aluno $aluno)
    {
        $validated = $request->validate([
            'nome_completo'   => 'sometimes|required|string|max:255',
            'email'           => 'sometimes|required|email|max:255|unique:alunos,email,' . $aluno->id,
            'cpf'             => 'sometimes|required|string|max:11|unique:alunos,cpf,' . $aluno->id,
            'telefone'        => 'nullable|string|max:20',
            'identidade'      => 'sometimes|required|string|max:20',
            'orgao_expedidor' => 'sometimes|required|string|max:20',
            'tipo_usuario'    => 'sometimes|required|in:Estudante,CRADT,Admin',
        ]);

        $aluno->update($validated);
        return response()->json($aluno);
    }

    public function destroy(Aluno $aluno)
    {
        // Verifica se o aluno tem matrículas ativas
        if ($aluno->matriculas()->where('status', 'Ativo')->exists()) {
            return response()->json(['error' => 'Não é possível excluir um aluno com matrículas ativas.'], 422);
        }

        $aluno->delete();
        return response()->json(null, 204);
    }

    // Validar o token
    public function validateToken(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json(['valid' => true, 'user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'error' => $e->getMessage()], 401);
        }
    }
}
