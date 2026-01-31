<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
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
                    // Garante que usa a tabela pivô correta
                    $newRequest->documents()->sync($request->document_ids);
                }

                return response()->json([
                    'message' => 'Requerimento criado com sucesso!',
                    'id' => $newRequest->id
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao salvar requerimento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function index()
    {
        $user = Auth::user();

        // ADICIONEI 'cradt' AQUI
        if (in_array($user->role, ['admin', 'staff', 'cradt'])) {
            return RequestModel::with(['user', 'type'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return RequestModel::with(['user', 'type'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function show($id)
    {
        $user = Auth::user();

        $request = RequestModel::with(['user.course', 'type', 'documents'])
            ->findOrFail($id);

        // ADICIONEI 'cradt' AQUI TAMBÉM
        if (!in_array($user->role, ['admin', 'staff', 'cradt']) && $request->user_id !== $user->id) {
            return response()->json([
                'message' => 'Acesso não autorizado'
            ], 403);
        }

        return response()->json($request);
    }
    // ... (Mantenha as funções store, index e show que já fizemos)

    // ATUALIZAR STATUS (Para a CRADT)
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $requestModel = RequestModel::findOrFail($id);

        // 1. Segurança: Só Admin/Staff/Cradt pode alterar status
        if (!in_array($user->role, ['admin', 'staff', 'cradt'])) {
            return response()->json(['message' => 'Apenas a coordenação pode alterar solicitações.'], 403);
        }

        // 2. Validação
        $validated = $request->validate([
            'status' => 'required|in:pending,analyzing,completed,rejected',
            'observation' => 'nullable|string'
        ]);

        // 3. Atualização
        $requestModel->update([
            'status' => $validated['status'],
            'observation' => $request->observation ?? $requestModel->observation
        ]);

        return response()->json([
            'message' => 'Status atualizado com sucesso!',
            'data' => $requestModel
        ]);
    }
}
