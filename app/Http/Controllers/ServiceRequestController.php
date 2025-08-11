<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ServiceRequestController extends Controller
{
    use AuthorizesRequests;

    /**
     * Exibe uma listagem do recurso.
     */
    public function index()
    {
        // Filtra as requisições com base no tipo de usuário.
        // Admins e Staffs veem todas as requisições.
        if (Auth::user()->role_id === 1 || Auth::user()->role_id === 2) {
            $requests = ServiceRequest::all();
        } else {
            // Estudantes veem apenas suas próprias requisições.
            $requests = ServiceRequest::where('user_id', Auth::user()->id)->get();
        }

        return response()->json($requests, 200);
    }

    /**
     * Armazena um recurso recém-criado no armazenamento.
     */
    public function store(Request $request)
    {
        // A política 'create' não requer um modelo, apenas o usuário.
        $this->authorize('create', ServiceRequest::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $request = ServiceRequest::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        return response()->json($request, 201);
    }

    /**
     * Exibe o recurso especificado.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        // O método 'show' usa a política 'view'.
        $this->authorize('view', $serviceRequest);

        return response()->json($serviceRequest, 200);
    }

    /**
     * Atualiza o recurso especificado no armazenamento.
     */
    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        // O método 'update' usa a política 'update'.
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|string|in:pending,in_progress,completed',
        ]);

        $serviceRequest->update($validated);

        return response()->json($serviceRequest, 200);
    }

    /**
     * Remove o recurso especificado do armazenamento.
     */
    public function destroy(ServiceRequest $serviceRequest)
    {
        // O método 'destroy' usa a política 'delete'.
        $this->authorize('delete', $serviceRequest);

        $serviceRequest->delete();

        return response()->noContent();
    }
}
