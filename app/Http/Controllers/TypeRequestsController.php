<?php

namespace App\Http\Controllers;

use App\Models\TypeRequest;
use App\Models\TypeDocument; // Importa o modelo TypeDocument para uso potencial
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // CORREÇÃO: Importa o Facade Log

class TypeRequestsController extends Controller
{
    /**
     * Lista todos os tipos de requerimentos.
     */
    public function index()
    {
        // Retorna todos os TypeRequests. Se houver eager loading necessário (ex: documentTypes), adicione-o aqui.
        $typeRequests = TypeRequest::all();
        return response()->json($typeRequests);
    }

    /**
     * Cria um novo tipo de requerimento.
     */
    public function store(Request $request)
    {
        try {
            // A validação usa o nome da tabela (plural)
            $validated = $request->validate([
                'name' => 'required|string|unique:type_requests,name|max:63'
            ]);

            $validated['id'] = (string) Str::uuid();

            // Usando o Model singular TypeRequest
            $typeRequests = TypeRequest::create($validated);

            return response()->json($typeRequests, 201); 
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Retorna erros de validação
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Loga o erro e retorna um erro genérico 500
            Log::error("Erro ao criar TypeRequest: " . $e->getMessage()); // Usa Log::error
            return response()->json(['error' => 'Erro ao criar tipo de requerimento.'], 500);
        }
    }

    /**
     * Exibe o tipo de requerimento especificado.
     * @param TypeRequest $typeRequest (Implicit Route Model Binding)
     */
    public function show(TypeRequest $typeRequest) 
    {
        // O Model Binding já tratou de encontrar o TypeRequest ou falhar com 404.
        return response()->json($typeRequest);
    }

    /**
     * Atualiza o tipo de requerimento especificado.
     * @param \Illuminate\Http\Request $request
     * @param TypeRequest $typeRequest (Implicit Route Model Binding)
     */
    public function update(Request $request, TypeRequest $typeRequest)
    {
        try {
            $validated = $request->validate([
                // 'name' deve ser único, exceto para o ID atual
                'name' => 'required|string|unique:type_requests,name,' . $typeRequest->id . '|max:63'
            ]);

            $typeRequest->update($validated);

            return response()->json($typeRequest, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar TypeRequest ID {$typeRequest->id}: " . $e->getMessage()); // Usa Log::error
            return response()->json(['error' => 'Erro ao atualizar tipo de requerimento.'], 500);
        }
    }

    /**
     * Remove o tipo de requerimento especificado.
     * @param TypeRequest $typeRequest (Implicit Route Model Binding)
     */
    public function destroy(TypeRequest $typeRequest)
    {
        try {
            // Adicione verificação de dependências antes de deletar (ex: se há Requests vinculados)
            $typeRequest->delete(); 
            return response()->json(['message' => 'Tipo de requerimento excluído com sucesso.'], 200);
        } catch (\Exception $e) {
            Log::error("Erro ao excluir TypeRequest ID {$typeRequest->id}: " . $e->getMessage()); // Usa Log::error
            return response()->json(['error' => 'Erro ao excluir tipo de requerimento.'], 500);
        }
    }

    /**
     * Sincroniza (adiciona/remove) tipos de documentos a um tipo de requerimento.
     * Este método corresponde ao 'syncDocumentTypes' esperado pela rota.
     * * Espera um array de IDs de documentos (document_type_ids).
     * @param \Illuminate\Http\Request $request
     * @param string $requestTypeId - O ID do TypeRequest (passado pela rota como {id})
     */
    public function syncDocumentTypes(Request $request, string $requestTypeId)
    {
        $validator = Validator::make(
            array_merge($request->all(), ['id' => $requestTypeId]),
            [
                'id' => 'required|uuid|exists:type_requests,id',
                // Espera um array de UUIDs de IDs de documentos
                'document_type_ids' => 'required|array',
                'document_type_ids.*' => 'required|uuid|exists:type_documents,id', 
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            // Usa o ID validado para encontrar o TypeRequest
            $requestType = TypeRequest::findOrFail($validated['id']);
            
            // Sincroniza: anexa os IDs fornecidos e desanexa todos os outros.
            $requestType->documentTypes()->sync($validated['document_type_ids']); 

            return response()->json(['message' => 'Documentos sincronizados com sucesso'], 200);
        } catch (\Exception $e) {
            Log::error("Erro ao sincronizar documentos para TypeRequest ID {$requestTypeId}: " . $e->getMessage()); // Usa Log::error
            return response()->json(['error' => 'Erro ao sincronizar documentos'], 500);
        }
    }
}
