<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    /**
     * Lista os requerimentos com FILTROS
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query base com relacionamentos
        $query = RequestModel::with(['user.course', 'type'])
            ->orderBy('created_at', 'desc');

        // Se for aluno, vê apenas os seus (forçando string/UUID)
        if ($user->role === 'student') {
            $query->where('user_id', (string) $user->getJWTIdentifier());
        }

        // Se for coordenação, vê apenas os requerimentos do curso vinculado
        if ($user->role === 'coordenacao') {
            $query->whereHas('user', function($q) use ($user) {
                $q->where('course_id', $user->course_id);
            });
        }

        // Filtros
        if ($request->filled('name')) {
            $name = $request->input('name');
            $query->whereHas('user', function($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        if ($request->filled('matricula')) {
            $matricula = $request->input('matricula');
            $query->whereHas('user', function($q) use ($matricula) {
                $q->where('enrollment_number', 'like', "%{$matricula}%")
                  ->orWhere('matricula', 'like', "%{$matricula}%");
            });
        }

        if ($request->filled('course_id')) {
            $courseId = $request->input('course_id');
            $query->whereHas('user', function($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        return $query->get();
    }

    /**
     * Cria um novo requerimento
     */
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
                // Captura o objeto completo do usuário autenticado via JWT
                $user = Auth::user();

                // Cria o requerimento garantindo o UUID puro em formato string extraído do token
                $newRequest = RequestModel::create([
                    'user_id' => (string) $user->getJWTIdentifier(), // 🔥 Blinda contra conversões numéricas (1, 3, etc)
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

    /**
     * Mostra detalhes de um requerimento
     */
    public function show($id)
    {
        $user = Auth::user();
        $request = RequestModel::with(['user.course', 'type', 'documents'])->findOrFail($id);

        // Admin, staff, cradt podem ver tudo
        if (in_array($user->role, ['admin', 'staff', 'cradt'])) {
            return response()->json($request);
        }

        // Coordenação só pode ver se o requerimento é do curso dela
        if ($user->role === 'coordenacao' && $request->user->course_id === $user->course_id) {
            return response()->json($request);
        }

        // Aluno só pode ver os seus
        if ($user->role === 'student' && $request->user_id === $user->id) {
            return response()->json($request);
        }

        return response()->json(['message' => 'Acesso não autorizado'], 403);
    }

    /**
     * Atualiza status/observação de um requerimento
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $req = RequestModel::findOrFail($id);

        if (!in_array($user->role, ['admin', 'staff', 'cradt'])) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $req->update([
            'status' => $request->status,
            'observation' => $request->observation ?? $req->observation
        ]);

        return response()->json(['message' => 'Atualizado']);
    }

    /**
     * Deleta um requerimento
     */
    public function destroy($id)
    {
        RequestModel::findOrFail($id)->delete();
        return response()->json(['message' => 'Deletado']);
    }
}