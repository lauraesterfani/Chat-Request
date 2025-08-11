<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TipoRequerimento;
use Illuminate\Http\Request;

class TipoRequerimentoController extends Controller
{
    public function index()
    {
        // Retorna os tipos e a contagem de anexos exigidos
        return TipoRequerimento::withCount('anexosExigidos')->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_requerimento' => 'required|string|max:150|unique:tipos_requerimento',
            'anexos_exigidos' => 'nullable|array',
            'anexos_exigidos.*' => 'exists:tipos_anexo,id_tipo_anexo', // Valida se cada ID de anexo existe
        ]);

        $tipoRequerimento = TipoRequerimento::create($validated);

        if (!empty($validated['anexos_exigidos'])) {
            $tipoRequerimento->anexosExigidos()->sync($validated['anexos_exigidos']);
        }

        return response()->json($tipoRequerimento->load('anexosExigidos'), 201);
    }

    public function show(TipoRequerimento $tipoRequerimento)
    {
        // Carrega a relação com os anexos exigidos
        return $tipoRequerimento->load('anexosExigidos');
    }

    public function update(Request $request, TipoRequerimento $tipoRequerimento)
    {
        $validated = $request->validate([
            'nome_requerimento' => 'sometimes|required|string|max:150|unique:tipos_requerimento,nome_requerimento,' . $tipoRequerimento->id_tipo_requerimento . ',id_tipo_requerimento',
            'anexos_exigidos' => 'nullable|array',
            'anexos_exigidos.*' => 'exists:tipos_anexo,id_tipo_anexo',
        ]);

        $tipoRequerimento->update($validated);

        if ($request->has('anexos_exigidos')) {
            $tipoRequerimento->anexosExigidos()->sync($validated['anexos_exigidos'] ?? []);
        }

        return response()->json($tipoRequerimento->load('anexosExigidos'));
    }

    public function destroy(TipoRequerimento $tipoRequerimento)
    {
        $tipoRequerimento->delete();
        return response()->json(null, 204);
    }
}