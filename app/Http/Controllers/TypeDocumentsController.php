<?php

namespace App\Http\Controllers;

use App\Models\TypeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TypeDocumentsController extends Controller
{
    /**
     * Display a listing of the resource.
     * Retorna todos os tipos de documentos cadastrados.
     */
    public function index()
    {
        // Retorna todos os documentos com status 200 OK
        return response()->json(TypeDocument::all());
    }

    /**
     * Store a newly created resource in storage.
     * Cria um novo tipo de documento e retorna 201 Created.
     */
    public function store(Request $request)
    {
        // O Laravel cuidará da exceção se a validação falhar, retornando 422
        $validated = $request->validate([
            'name' => 'required|string|unique:type_documents,name|max:63',
            'description' => 'required|string|max:255' // Adicionado o description à validação
        ]);

        // Gerando o UUID para o campo 'id'
        $validated['id'] = (string) Str::uuid();

        // Criação do registro no banco
        $typeDocument = TypeDocument::create($validated);

        // Retorna o novo recurso criado com status 201 Created
        return response()->json($typeDocument, 201);
    }

    /**
     * Display the specified resource.
     * Retorna um tipo de documento específico.
     */
    public function show(TypeDocument $typeDocument)
    {
        // O Laravel injeta o Model e retorna ele (ou 404 se não for encontrado)
        return response()->json($typeDocument);
    }

    /**
     * Update the specified resource in storage.
     * Atualiza um tipo de documento.
     */
    public function update(Request $request, TypeDocument $typeDocument)
    {
        $validated = $request->validate([
            // Ignora o nome atual ao checar a unicidade, usando o ID do model injetado
            'name' => 'sometimes|required|string|unique:type_documents,name,' . $typeDocument->id . '|max:63',
            'description' => 'sometimes|required|string|max:255'
        ]);

        $typeDocument->update($validated);

        return response()->json($typeDocument);
    }

    /**
     * Remove the specified resource from storage.
     * Remove um tipo de documento.
     */
    public function destroy(TypeDocument $typeDocument)
    {
        $typeDocument->delete();

        // Retorna 204 No Content para indicar sucesso na exclusão sem corpo de resposta
        return response()->json(null, 204);
    }
}
