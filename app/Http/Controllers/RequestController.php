<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    /**
     * Lista os requerimentos com FILTROS (Recuperado)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Query Base (Carrega relacionamentos)
        $query = RequestModel::with(['user.course', 'type'])
            ->orderBy('created_at', 'desc');

        // 2. Segurança: Se for aluno, vê apenas os seus
        if ($user->role === 'student') {
            $query->where('user_id', $user->id);
        }

        // --- FILTROS DE BUSCA ---

        // Filtro por Nome
        if ($request->filled('name')) {
            $name = $request->input('name');
            $query->whereHas('user', function($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        // Filtro por Matrícula
        if ($request->filled('matricula')) {
            $matricula = $request->input('matricula');
            $query->whereHas('user', function($q) use ($matricula) {
                $q->where('enrollment_number', 'like', "%{$matricula}%")
                  ->orWhere('matricula', 'like', "%{$matricula}%");
            });
        }

        // Filtro por Curso (ID)
        if ($request->filled('course_id')) {
            $courseId = $request->input('course_id');
            $query->whereHas('user', function($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        return $query->get();
    }

    // --- MANTENHA O RESTO IGUAL (Store, Show, Update...) ---
    
    public function store(Request $request)
    {
        $request->validate([
            'type_id' => 'required|exists:type_requests,id',
            'subject' => 'required|string',
            'description' => 'required|string',
            'document_ids' => 'array'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $newRequest = RequestModel::create([
                    'user_id' => Auth::id(),
                    'type_id' => $request->type_id,
                    'subject' => $request->subject,
                    'description' => $request->description,
                    'status' => 'pending',
                    'protocol' => now()->format('Ymd') . '-' . rand(1000, 9999)
                ]);

                if (!empty($request->document_ids)) {
                    $newRequest->documents()->sync($request->document_ids);
                }

                return response()->json(['message' => 'Sucesso', 'id' => $newRequest->id], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $request = RequestModel::with(['user.course', 'type', 'documents'])->findOrFail($id);

        if (!in_array($user->role, ['admin', 'staff', 'cradt']) && $request->user_id !== $user->id) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        return response()->json($request);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $req = RequestModel::findOrFail($id); // Corrigido para buscar o modelo

        if (!in_array($user->role, ['admin', 'staff', 'cradt'])) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $req->update([
            'status' => $request->status,
            'observation' => $request->observation ?? $req->observation
        ]);

        return response()->json(['message' => 'Atualizado']);
    }

    public function destroy($id)
    {
        RequestModel::findOrFail($id)->delete();
        return response()->json(['message' => 'Deletado']);
    }
}