<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        // 1. Valida se o arquivo existe e é uma imagem ou PDF
        $request->validate([
            'arquivo' => 'required|file|mimes:pdf,jpg,png|max:2048',
        ]);

        try {
            // 2. Salva o arquivo na pasta 'public/documents'
            $path = $request->file('arquivo')->store('documents', 'public');

            // 3. Cria o registro no banco usando UUID
            $document = Document::create([
                'path' => $path,
                'name' => $request->file('arquivo')->getClientOriginalName(),
                'mime_type' => $request->file('arquivo')->getClientMimeType(),
            ]);

            return response()->json([
                'id' => $document->id, // O frontend precisa desse UUID
                'url' => Storage::url($path)
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['msg' => 'Erro no upload: ' . $e->getMessage()], 500);
        }
    }
}