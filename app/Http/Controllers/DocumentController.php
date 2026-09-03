<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        // 1. Valida se o arquivo existe de acordo com a chave 'arquivo' enviada pelo FormData
        $request->validate([
            'arquivo' => 'required|file|mimetypes:application/pdf,image/jpeg,image/png|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $user = Auth::guard('api')->user()
            ?? Auth::guard('staff_admins')->user()
            ?? $request->user();
        if (! $user instanceof User) {
            return response()->json(['message' => 'Apenas alunos podem enviar documentos.'], 403);
        }

        $storedPath = null;
        try {
            if (! $request->hasFile('arquivo') || ! $request->file('arquivo')->isValid()) {
                return response()->json(['message' => 'Arquivo inválido ou corrompido.'], 422);
            }

            $uploadedFile = $request->file('arquivo');
            $storedPath = $uploadedFile->store('documents', 'public');

            $document = Document::create([
                'path' => $storedPath,
                'name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getMimeType(),
                'user_id' => $user->getKey(),
                'file_size' => $uploadedFile->getSize(),
            ]);

            return response()->json([
                'id' => (string) $document->id, // Força a conversão explícita para string (UUID)
                'url' => Storage::url($storedPath),
            ], 201);

        } catch (\Exception $e) {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            report($e);

            return response()->json(['message' => 'Não foi possível processar o documento.'], 500);
        }
    }
}
