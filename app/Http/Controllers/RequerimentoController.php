<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Requerimento;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequerimentoController extends Controller
{
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

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_matricula'           => 'required|exists:matriculas,id_matricula',
            'id_tipo_requerimento'   => 'required|exists:tipos_requerimento,id_tipo_requerimento',
            'observacoes'            => 'nullable|string',
            'anexos'                 => 'nullable|array',
            'anexos.*.file'          => 'required_with:anexos|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
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
                        $file        = $fileData['file'];
                        $idTipoAnexo = $request->input("anexos.$index.id_tipo_anexo");
                        $path        = $file->store('anexos', 'public');

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

    public function show($id)
    {
        try {

            $requerimento = Requerimento::with([
                'matricula.aluno',
                'matricula.curso',
                'matricula.campus',
                'tipoRequerimento',
                'anexos.tipoAnexo', // Carrega os anexos e o tipo de cada anexo
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

    public function updateStatus(Request $request, Requerimento $requerimento)
    {
        // 1. Valida o novo status recebido
        $validated = $request->validate([
            'status' => 'required|in:Aberto,Em Análise,Deferido,Indeferido,Pendente',
        ]);

        try {
            // 2. Atualiza o status do requerimento
            $requerimento->status = $validated['status'];

            // 3. Se o status for finalizado, adiciona a data de finalização
            if (in_array($validated['status'], ['Deferido', 'Indeferido'])) {
                $requerimento->data_finalizacao = now();
            } else {
                // Caso o status seja revertido, limpa a data de finalização
                $requerimento->data_finalizacao = null;
            }

            // 4. Salva as alterações no banco de dados
            $requerimento->save();

            // 5. Retorna o requerimento atualizado com sucesso
            return response()->json($requerimento);

        } catch (\Exception $e) {
            Log::error("Erro ao atualizar status do requerimento ID {$requerimento->id_requerimento}: " . $e->getMessage());
            return response()->json(['message' => 'Ocorreu um erro interno ao atualizar o status.'], 500);
        }
    }

}
