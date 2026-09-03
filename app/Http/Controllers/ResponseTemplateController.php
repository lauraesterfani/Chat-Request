<?php

namespace App\Http\Controllers;

use App\Models\ResponseTemplate;
use App\Models\StaffAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResponseTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:active,inactive'],
            'type_request_id' => ['nullable', 'uuid', 'exists:type_requests,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $templates = ResponseTemplate::query()
            ->with('typeRequests:id,name')
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->filled('type_request_id'), fn ($query) => $query->whereHas(
                'typeRequests',
                fn ($types) => $types->whereKey($request->input('type_request_id'))
            ))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(fn ($inner) => $inner->where('title', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%"));
            })
            ->orderBy('title');

        return response()->json($templates->paginate(min((int) $request->input('per_page', 20), 100)));
    }

    public function active(Request $request): JsonResponse
    {
        $request->validate([
            'type_request_id' => ['nullable', 'uuid', 'exists:type_requests,id'],
        ]);

        $templates = ResponseTemplate::query()
            ->with('typeRequests:id,name')
            ->where('is_active', true)
            ->when($request->filled('type_request_id'), fn ($query) => $query->whereHas(
                'typeRequests',
                fn ($types) => $types->whereKey($request->input('type_request_id'))
            ))
            ->orderBy('title')
            ->get();

        return response()->json($templates);
    }

    public function show(ResponseTemplate $responseTemplate): JsonResponse
    {
        return response()->json($responseTemplate->load('typeRequests:id,name'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateTemplate($request);

        $template = DB::transaction(function () use ($validated, $request) {
            $template = ResponseTemplate::create([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_active' => $validated['is_active'] ?? true,
                // A equipe administrativa vive em staff_admins. Mantemos nulo
                // caso uma instalação legada ainda autentique staff pela tabela users.
                'created_by' => $request->user() instanceof StaffAdmin ? $request->user()->id : null,
            ]);

            $template->typeRequests()->sync($validated['type_request_ids']);

            return $template;
        });

        return response()->json($template->load('typeRequests:id,name'), 201);
    }

    public function update(Request $request, ResponseTemplate $responseTemplate): JsonResponse
    {
        $validated = $this->validateTemplate($request);

        DB::transaction(function () use ($validated, $responseTemplate) {
            $responseTemplate->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_active' => $validated['is_active'] ?? $responseTemplate->is_active,
            ]);
            $responseTemplate->typeRequests()->sync($validated['type_request_ids']);
        });

        return response()->json($responseTemplate->fresh()->load('typeRequests:id,name'));
    }

    public function updateStatus(Request $request, ResponseTemplate $responseTemplate): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $responseTemplate->update($validated);

        return response()->json($responseTemplate->load('typeRequests:id,name'));
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string', 'max:5000', 'not_regex:/^\s*$/'],
            'is_active' => ['sometimes', 'boolean'],
            'type_request_ids' => ['required', 'array', 'min:1'],
            'type_request_ids.*' => ['required', 'uuid', 'distinct', 'exists:type_requests,id'],
        ]);
    }
}
