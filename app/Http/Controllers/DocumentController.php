<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    // MUDAMOS O NOME DE 'store' PARA 'upload' PARA BATER COM A SUA ROTA
    public function upload(Request $request) 
    {
        $request->validate([
            'arquivo' => 'required|file|max:10240',
        ]);

        try {
            if ($request->hasFile('arquivo')) {
                $file = $request->file('arquivo');
                $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('documents', $fileName, 'public');

                $document = Document::create([
                    'user_id'   => $request->user()->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);

                return response()->json($document, 201);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Erro ao processar arquivo.'], 400);
    }
}