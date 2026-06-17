<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        // 1. Valida se o arquivo existe de acordo com a chave 'arquivo' enviada pelo FormData
        $request->validate([
            'arquivo' => 'required|file|mimes:pdf,jpg,png|max:10240',
        ]);

        try {
            if (!$request->hasFile('arquivo') || !$request->file('arquivo')->isValid()) {
                return response()->json(['error' => 'Arquivo inválido ou corrompido.'], 400);
            }

            // 2. Salva o arquivo na pasta 'public/documents'
            $path = $request->file('arquivo')->store('documents', 'public');

            // 3. Cria o registro no banco de dados
            $document = Document::create([
                'path' => $path,
                'name' => $request->file('arquivo')->getClientOriginalName(),
                'mime_type' => $request->file('arquivo')->getClientMimeType(),
            ]);

            return response()->json([
                'id' => (string) $document->id, // Força a conversão explícita para string (UUID)
                'url' => Storage::url($path)
            ], 201);

        } catch (\Exception $e) {
            // Retorna o erro real com status 500 para o front-end capturar e exibir no chat
            return response()->json([
                'error' => 'Erro no servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}