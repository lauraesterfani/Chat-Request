<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TypeRequest;
use App\Models\TypeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TypeRequestController extends Controller
{
    /**
     * Lista todos os tipos de requerimentos.
     * (PÚBLICO - Usado pelo Chat e pelo Admin)
     */
    public function index()
    {
        $typeRequests = TypeRequest::orderBy('name', 'asc')->get();
        return response()->json($typeRequests);
    }

    /**
     * Cria um novo tipo de requerimento.
     * (ADMIN)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:type_requests,name|max:63',
                'description' => 'nullable|string',
                'icon' => 'nullable|string'
            ]);

            $validated['id'] = (string) Str::uuid();

            $typeRequest = TypeRequest::create($validated);

            return response()->json($typeRequest, 201); 
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Erro ao criar TypeRequest: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao criar tipo de requerimento.'], 500);
        }
    }

    /**
     * Exibe o tipo de requerimento especificado.
     * (ADMIN)
     */
    public function show($id) 
    {
        $typeRequest = TypeRequest::find($id);
        if (!$typeRequest) return response()->json(['error' => 'Not found'], 404);
        return response()->json($typeRequest);
    }

    /**
     * Atualiza o tipo de requerimento especificado.
     * (ADMIN)
     */
    public function update(Request $request, $id)
    {
        try {
            $typeRequest = TypeRequest::find($id);
            if (!$typeRequest) return response()->json(['error' => 'Not found'], 404);

            $validated = $request->validate([
                'name' => 'required|string|max:63|unique:type_requests,name,' . $id,
                'description' => 'nullable|string',
                'icon' => 'nullable|string'
            ]);

            $typeRequest->update($validated);

            return response()->json($typeRequest, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao atualizar.'], 500);
        }
    }

    /**
     * Remove o tipo de requerimento especificado.
     * (ADMIN)
     */
    public function destroy($id)
    {
        try {
            $typeRequest = TypeRequest::find($id);
            if (!$typeRequest) return response()->json(['error' => 'Not found'], 404);

            $typeRequest->delete(); 
            return response()->json(['message' => 'Excluído com sucesso.'], 200);
        } catch (\Exception $e) {
            Log::error("Erro ao excluir: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao excluir.'], 500);
        }
    }

    /**
     * Sincroniza documentos a um tipo de requerimento.
     * (ADMIN)
     */
    public function syncDocumentTypes(Request $request, string $requestTypeId)
    {
        $validator = Validator::make(
            array_merge($request->all(), ['id' => $requestTypeId]),
            [
                'id' => 'required|exists:type_requests,id',
                'document_type_ids' => 'required|array',
                'document_type_ids.*' => 'required|exists:type_documents,id', 
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $requestType = TypeRequest::findOrFail($requestTypeId);
            // Garanta que o Model TypeRequest tem o método documentTypes()
            $requestType->documentTypes()->sync($request->document_type_ids); 

            return response()->json(['message' => 'Sincronizado com sucesso'], 200);
        } catch (\Exception $e) {
            Log::error("Erro sync: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao sincronizar'], 500);
        }
    }
}