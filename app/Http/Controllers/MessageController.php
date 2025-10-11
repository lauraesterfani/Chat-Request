<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    /**
     * Lógica para armazenar uma nova mensagem de chat.
     * Esta é a SEGUNDA ETAPA no fluxo de upload de anexo.
     * * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // 1. VALIDAÇÃO DA MENSAGEM
            $request->validate([
                'receiver_id' => 'required|exists:users,id', // O destinatário deve ser um ID de usuário válido.
                'content' => 'nullable|string|max:2000',     // O texto da mensagem é opcional (se houver anexo).
                // document_id é opcional, mas se enviado, deve existir na tabela 'documents'.
                'document_id' => 'nullable|exists:documents,id', 
            ]);

        } catch (ValidationException $e) {
            // Retorna erros de validação com status 422
            return response()->json([
                'mensagem' => 'Erro de validação ao enviar mensagem.',
                'erros' => $e->errors()
            ], 422);
        }

        // 2. REGRA DE NEGÓCIO: Garante que a mensagem tenha conteúdo OU um anexo.
        if (empty($request->content) && empty($request->document_id)) {
            return response()->json(['mensagem' => 'Mensagem ou anexo é obrigatório.'], 422);
        }
        
        // 3. CRIAÇÃO DO REGISTRO DA MENSAGEM
        $message = Message::create([
            'sender_id' => $request->user()->id, // ID do usuário autenticado (via Sanctum/auth:api).
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'document_id' => $request->document_id, // Vincula o ID do documento upado na 1ª etapa.
            
            // Define o tipo com base na presença do anexo.
            'type' => $request->document_id ? 'file' : 'text', 
        ]);
        
       

        // 5. RESPOSTA PARA O FRONTEND
        // Retorna a mensagem completa, carregando o relacionamento 'document' para que o Front
        // possa exibir o nome e URL do arquivo.
        return response()->json($message->load('document'), 201);
    }
}
