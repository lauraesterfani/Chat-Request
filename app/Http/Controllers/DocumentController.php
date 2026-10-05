<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Request as RequestModel;
use App\Models\StaffAdmin;
use App\Models\User;
use App\Services\RequestAccessService;
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
            $storedPath = $uploadedFile->store('documents', 'local');

            $document = Document::create([
                'path' => $storedPath,
                'name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getMimeType(),
                'user_id' => $user->getKey(),
                'file_size' => $uploadedFile->getSize(),
            ]);

            return response()->json([
                'id' => (string) $document->id, // Força a conversão explícita para string (UUID)
                'url' => null,
            ], 201);

        } catch (\Exception $e) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            report($e);

            return response()->json(['message' => 'Não foi possível processar o documento.'], 500);
        }
    }

    public function show(Request $request, RequestModel $requestModel, Document $document)
    {
        $actor = Auth::guard('api')->user() ?? Auth::guard('staff_admins')->user() ?? $request->user();
        if (! ($actor instanceof User || $actor instanceof StaffAdmin)
            || ! app(RequestAccessService::class)->canView($actor, $requestModel)
            || ! $requestModel->documents()->whereKey($document->id)->exists()) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        $disk = Storage::disk('local')->exists($document->path) ? 'local' : 'public';
        if (! Storage::disk($disk)->exists($document->path)) {
            return response()->json(['message' => 'Documento não encontrado.'], 404);
        }

        return response()->file(Storage::disk($disk)->path($document->path), [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="documento"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
