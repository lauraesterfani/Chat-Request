<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    /**
     * Recebe um arquivo do frontend, salva-o no disco e registra seus metadados no banco.
     * Retorna o ID do novo documento para ser usado no envio da mensagem.
     */
    public function upload(Request $request)
    {
        // 1. Validação do Arquivo
        try {
            // Regras: obrigatório, arquivo válido, máx. 10MB, tipos permitidos para chat.
            $request->validate([
                'arquivo' => 'required|file|max:10240|mimes:jpeg,png,jpg,gif,pdf,zip,doc,docx',
            ]);
        } catch (ValidationException $e) {
            // Retorna erro de validação com status 422 (Unprocessable Entity)
            return response()->json(['mensagem' => 'Erro de validação', 'erros' => $e->errors()], 422);
        }

        if ($request->hasFile('arquivo')) {
            $file = $request->file('arquivo');
            
            // O tipo de documento foi removido para evitar falhas se a tabela não existir.
            // Para produção, substitua pela lógica real: 
            // $type_document_id = TypeDocument::where('slug', 'chat-attachment')->value('id');

            // 2. Salva o arquivo no disco 'public' (pasta 'uploads')
            // $path conterá o caminho relativo (ex: 'uploads/abcde12345.jpg')
            $path = $file->store('uploads', 'public');

            // 3. Cria o Registro no Banco de Dados
            $document = Document::create([
                'user_id' => $request->user()->id, // Obtém o ID do usuário autenticado
                // 'type_document_id' => $chat_document_type_id, // Campo opcional por enquanto
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);

            // 4. Resposta de Sucesso para o Frontend
            return response()->json([
                'mensagem' => 'Arquivo enviado e registrado com sucesso.',
                'document_id' => $document->id, // ESSENCIAL: ID do documento
                'public_url' => Storage::url($path), // URL pública para visualização
            ], 201);
        }

        return response()->json(['mensagem' => 'Nenhum arquivo enviado.'], 400);
    }
}
