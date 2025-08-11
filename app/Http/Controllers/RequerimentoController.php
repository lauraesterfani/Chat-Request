<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Requerimento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RequerimentoController extends Controller
{
    /**
     * Lista todos os requerimentos com filtros e paginação.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Inicia a query com o carregamento dos relacionamentos para evitar N+1 queries
        $query = Requerimento::with([
            'matricula.aluno',
            'matricula.curso',
            'tipoRequerimento',
        ]);

        // Filtro opcional por status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filtro opcional por nome do aluno ou CPF
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('matricula.aluno', function ($q) use ($searchTerm) {
                $q->where('nome_completo', 'like', "%{$searchTerm}%")
                    ->orWhere('cpf', 'like', "%{$searchTerm}%");
            });
        }

        // Ordena pelos mais recentes e retorna com paginação
        return $query->latest()->paginate(15);
    }

    /**
     * Cria um novo requerimento com seus anexos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_matricula'         => 'required|exists:matriculas,id_matricula',
            'id_tipo_requerimento' => 'required|exists:tipos_requerimento,id_tipo_requerimento',
            'observacoes'          => 'nullable|string',
            'anexos'               => 'nullable|array',
            'anexos.*.file'        => 'required_with:anexos|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
            'anexos.*.id_tipo_anexo' => 'required_with:anexos|exists:tipos_anexo,id_tipo_anexo',
        ]);

        try {
            $requerimento = DB::transaction(function () use ($request, $validatedData) {
                $requerimento = Requerimento::create([
                    'id_matricula'         => $validatedData['id_matricula'],
                    'id_tipo_requerimento' => $validatedData['id_tipo_requerimento'],
                    'observacoes'          => $validatedData['observacoes'] ?? null,
                    'protocolo'            => date('Ymd') . '-' . mt_rand(1000, 9999),
                    'status'               => 'Aberto',
                ]);

                if ($request->hasFile('anexos')) {
                    foreach ($request->file('anexos') as $index => $fileData) {
                        $file = $fileData['file'];
                        $idTipoAnexo = $request->input("anexos.$index.id_tipo_anexo");
                        $path = $file->store('anexos', 'public');

                        $requerimento->anexos()->create([
                            'id_tipo_anexo'   => $idTipoAnexo,
                            'caminho_arquivo' => $path,
                            'nome_original'   => $file->getClientOriginalName(),
                        ]);
                    }
                }
                return $requerimento->load('anexos.tipoAnexo');
            });

            return response()->json($requerimento, 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar requerimento: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ocorreu um erro interno ao processar a solicitação.',
            ], 500);
        }
    }

    /**
     * Exibe um requerimento específico com todos os seus relacionamentos.
     *
     * @param  int  $id O ID do requerimento a ser buscado.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Busca o requerimento pelo ID com todos os relacionamentos necessários.
            // O with() evita o problema de N+1 queries, carregando tudo de uma vez.
            $requerimento = Requerimento::with([
                'matricula.aluno',
                'matricula.curso',
                'matricula.campus',
                'tipoRequerimento',
                'anexos.tipoAnexo' // Carrega os anexos e o tipo de cada anexo
            ])->findOrFail($id);

            // Retorna o requerimento encontrado como JSON com status 200 (OK)
            return response()->json($requerimento);

        } catch (ModelNotFoundException $e) {
            // O findOrFail já lida com isso, mas um catch explícito é bom para clareza.
            // Laravel automaticamente retornará 404.
            return response()->json(['message' => 'Requerimento não encontrado.'], 404);
        } catch (\Exception $e) {
            // Captura qualquer outro erro inesperado
            Log::error("Erro ao buscar requerimento ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Ocorreu um erro interno ao buscar o requerimento.'], 500);
        }
    }

    // ... outros métodos do controller (update, destroy) ...
}
