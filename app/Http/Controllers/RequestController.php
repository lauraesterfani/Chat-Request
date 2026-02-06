<?php

namespace App\Http\Controllers;

use App\Models\Request as Requerimento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    /**
     * Lista os requerimentos (Inteligente: Aluno vê o seu, Admin vê tudo)
     */
    public function index()
    {
        $user = Auth::user();

        // 1. Se for Admin, Staff ou CRADT -> Vê TUDO
        if (in_array($user->role, ['admin', 'staff', 'cradt'])) {
            return Requerimento::with(['user.course', 'type']) // Carrega dados do aluno e curso
                ->latest()
                ->get();
        }

        // 2. Se for Aluno -> Vê só os DELE
        return Requerimento::with(['type']) // Aluno não precisa ver seus próprios dados de user
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    /**
     * Cria um novo requerimento
     */
    public function store(Request $request)
    {
        $request->validate([
            'type_id' => 'required|exists:type_requests,id',
            'description' => 'nullable|string',
            'documents' => 'array'
        ]);

        // Cria o requerimento
        $req = Requerimento::create([
            'user_id' => Auth::id(),
            'type_id' => $request->type_id,
            'subject' => \App\Models\TypeRequest::find($request->type_id)->name, // Salva o nome do assunto
            'description' => $request->description,
            'status' => 'pending',
            'protocol' => strtoupper(uniqid('REQ-')), // Gera protocolo único
        ]);

        // Se tiver documentos (array de IDs vindos do upload)
        if ($request->has('documents')) {
            // Atualiza os documentos para pertencerem a este requerimento
            \App\Models\Document::whereIn('id', $request->documents)
                ->update(['request_id' => $req->id]);
        }

        return response()->json($req, 201);
    }

    /**
     * Mostra um requerimento específico
     */
    public function show($id)
    {
        $user = Auth::user();
        $req = Requerimento::with(['user.course', 'type', 'documents'])->findOrFail($id);

        // Segurança: Só deixa ver se for dono OU for Admin/Staff/Cradt
        if ($req->user_id !== $user->id && !in_array($user->role, ['admin', 'staff', 'cradt'])) {
            return response()->json(['message' => 'Sem permissão'], 403);
        }

        return response()->json($req);
    }

    /**
     * Atualiza (Usado para mudar status ou editar)
     */
    public function update(Request $request, $id)
    {
        $req = Requerimento::findOrFail($id);
        
        // Apenas valida status se for enviado
        if ($request->has('status')) {
            $req->status = $request->status;
        }

        if ($request->has('observation')) {
            $req->observation = $request->observation;
        }

        $req->save();

        return response()->json($req);
    }

    public function destroy($id)
    {
        $req = Requerimento::findOrFail($id);
        $req->delete();
        return response()->json(['message' => 'Deletado com sucesso']);
    }
}