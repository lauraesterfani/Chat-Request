<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Requerimento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequerimentoController extends Controller
{
    /**
     * Cria um novo requerimento com seus anexos.
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

    public function store(Request $request)
    {
        // 1. VALIDAÇÃO
        // A validação permanece a mesma, pois é robusta.
        // Se falhar, o Laravel automaticamente retorna uma resposta 422 com os erros.
        $validatedData = $request->validate([
            'id_matricula'           => 'required|exists:matriculas,id_matricula',
            'id_tipo_requerimento'   => 'required|exists:tipos_requerimento,id_tipo_requerimento',
            'observacoes'            => 'nullable|string',
            'anexos'                 => 'nullable|array',                                            // 'nullable' em vez de 'required' para permitir requerimentos sem anexos
            'anexos.*.file'          => 'required_with:anexos|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
            'anexos.*.id_tipo_anexo' => 'required_with:anexos|exists:tipos_anexo,id_tipo_anexo',
        ]);

        // 2. TRANSAÇÃO E LÓGICA DE CRIAÇÃO
        // Usar uma closure com DB::transaction é mais seguro e limpo.
        // Ele automaticamente faz o commit em caso de sucesso ou rollback em caso de erro.
        try {
            $requerimento = DB::transaction(function () use ($request, $validatedData) {
                // Cria o registro principal do requerimento
                $requerimento = Requerimento::create([
                    'id_matricula'         => $validatedData['id_matricula'],
                    'id_tipo_requerimento' => $validatedData['id_tipo_requerimento'],
                    'observacoes'          => $validatedData['observacoes'],
                    'protocolo'            => date('Ymd') . '-' . mt_rand(1000, 9999), // Protocolo único
                    'status'               => 'Aberto',
                ]);

                // Processa e salva cada anexo, se houver
                if ($request->hasFile('anexos')) {
                    foreach ($request->file('anexos') as $index => $file) {
                        // Pega o id_tipo_anexo correspondente do input
                        $idTipoAnexo = $request->input("anexos.$index.id_tipo_anexo");

                        // Salva o arquivo no disco 'public' dentro da pasta 'anexos'
                        $path = $file->store('anexos', 'public');

                        // Cria o registro do anexo associado ao requerimento
                        $requerimento->anexos()->create([
                            'id_tipo_anexo'   => $idTipoAnexo,
                            'caminho_arquivo' => $path,
                            'nome_original'   => $file->getClientOriginalName(),
                        ]);
                    }
                }

                // Retorna o requerimento com seus anexos carregados
                return $requerimento->load('anexos.tipoAnexo');
            });

            // 3. RESPOSTA DE SUCESSO
            // Retorna o objeto completo do requerimento criado e o status 201 (Created)
            return response()->json($requerimento, 201);

        } catch (\Exception $e) {
            // 4. RESPOSTA DE ERRO
            // Registra o erro detalhado no log do Laravel para depuração
            Log::error('Erro ao criar requerimento: ' . $e->getMessage());

            // Retorna uma resposta de erro genérica para o cliente da API
            return response()->json([
                'message' => 'Ocorreu um erro interno ao processar a solicitação.',
            ], 500);
        }
    }

    // ... outros métodos do controller (index, show, update, destroy) ...
}
