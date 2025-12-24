<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validação
        $request->validate([
            'type_id' => 'required|exists:type_requests,id',
            'subject' => 'required|string',
            'description' => 'required|string',
            'document_ids' => 'array'
        ]);

        try {
            // Inicia uma transação (se der erro, cancela tudo)
            return DB::transaction(function () use ($request) {
                
                // 2. Criar o Requerimento
                $newRequest = RequestModel::create([
                    'user_id' => Auth::id(), // Pega o ID do usuário logado
                    'type_id' => $request->type_id,
                    'subject' => $request->subject,
                    'description' => $request->description,
                    'status' => 'pending',
                   'protocol'    => now()->format('Ymd') . '-' . rand(1000, 9999)
                ]);

                // 3. Vincular os Documentos (Preenche a tabela pivô)
                if (!empty($request->document_ids)) {
                    $newRequest->documents()->sync($request->document_ids);
                }

                return response()->json([
                    'message' => 'Requerimento criado com sucesso!',
                    'id' => $newRequest->id
                ], 201);
            });

        } catch (\Exception $e) {
            // Mostra o erro real no console do navegador se falhar
            return response()->json([
                'message' => 'Erro ao salvar requerimento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Listar meus pedidos (Bônus para a Dashboard)
    public function index()
    {
        return RequestModel::with('type')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
    }
}