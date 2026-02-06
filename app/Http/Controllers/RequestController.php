<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Query Base
        $query = RequestModel::with(['user.course', 'type'])
            ->orderBy('created_at', 'desc');

        // 2. Segurança: Aluno vê apenas os seus
        if ($user->role === 'student') {
            $query->where('user_id', $user->id);
        }

        // --- FILTROS ---

        // Nome
        if ($request->filled('name')) {
            $name = $request->input('name');
            $query->whereHas('user', function($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        // Matrícula
        if ($request->filled('matricula')) {
            $matricula = $request->input('matricula');
            $query->whereHas('user', function($q) use ($matricula) {
                $q->where('enrollment_number', 'like', "%{$matricula}%")
                  ->orWhere('matricula', 'like', "%{$matricula}%");
            });
        }

        // Curso
        if ($request->filled('course_id')) {
            $courseId = $request->input('course_id');
            $query->whereHas('user', function($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        return $query->get();
    }

    // --- MANTENHA AS OUTRAS FUNÇÕES (Store, Show, Update...) ---
    // (Pode deixar as que já estão lá, só a index que precisava mudar)
    
    public function store(Request $request)
    {
        // ... (seu código de store existente) ...
        // Vou resumir aqui para não ficar gigante, mas mantenha o seu store original
        $request->validate(['type_id' => 'required', 'subject' => 'required', 'description' => 'required']);
        
        return DB::transaction(function () use ($request) {
            $req = RequestModel::create([
                'user_id' => Auth::id(),
                'type_id' => $request->type_id,
                'subject' => $request->subject,
                'description' => $request->description,
                'status' => 'pending',
                'protocol' => now()->format('Ymd') . '-' . rand(1000, 9999)
            ]);
            if (!empty($request->document_ids)) {
                $req->documents()->sync($request->document_ids);
            }
            return response()->json($req, 201);
        });
    }

    public function show($id)
    {
        $user = Auth::user();
        $req = RequestModel::with(['user.course', 'type', 'documents'])->findOrFail($id);
        if (!in_array($user->role, ['admin', 'staff', 'cradt']) && $req->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json($req);
    }

    public function update(Request $request, $id)
    {
        $req = RequestModel::findOrFail($id);
        $req->update($request->only(['status', 'observation']));
        return response()->json($req);
    }
    
    public function destroy($id) { RequestModel::destroy($id); return response()->json(['ok'=>true]); }
}