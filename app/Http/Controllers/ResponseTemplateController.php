<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Models\ResponseTemplate;
use App\Models\StaffAdmin;
use App\Services\RequestAccessService;
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
            'sector' => ['nullable', 'in:CRADT,COORDENACAO'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $templates = ResponseTemplate::query()
            ->with('typeRequests:id,name')
            ->whereIn('sector', $this->allowedSectors($request))
            ->when($request->filled('sector'), fn ($query) => $query->where('sector', $request->input('sector')))
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
            'request_id' => ['required', 'uuid', 'exists:requests,id'],
        ]);

        $requestModel = RequestModel::with('user')->findOrFail($request->input('request_id'));
        abort_unless(app(RequestAccessService::class)->canView($request->user(), $requestModel), 403);

        $templates = ResponseTemplate::query()
            ->with('typeRequests:id,name')
            ->where('is_active', true)
            ->where('sector', $requestModel->responsible_sector)
            ->whereHas('typeRequests', fn ($types) => $types->whereKey($requestModel->type_id))
            ->orderBy('title')
            ->get();

        return response()->json($templates);
    }

    public function show(Request $request, ResponseTemplate $responseTemplate): JsonResponse
    {
        $this->assertSectorAccess($request, $responseTemplate->sector);

        return response()->json($responseTemplate->load('typeRequests:id,name'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateTemplate($request);
        $sector = $validated['sector'] ?? ($request->user()?->role === 'coordenacao' ? 'COORDENACAO' : 'CRADT');
        $this->assertSectorAccess($request, $sector);

        $template = DB::transaction(function () use ($validated, $request, $sector) {
            $template = ResponseTemplate::create([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_active' => $validated['is_active'] ?? true,
                'sector' => $sector,
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
        $this->assertSectorAccess($request, $responseTemplate->sector);
        $sector = $validated['sector'] ?? $responseTemplate->sector;
        $this->assertSectorAccess($request, $sector);

        DB::transaction(function () use ($validated, $responseTemplate, $sector) {
            $responseTemplate->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_active' => $validated['is_active'] ?? $responseTemplate->is_active,
                'sector' => $sector,
            ]);
            $responseTemplate->typeRequests()->sync($validated['type_request_ids']);
        });

        return response()->json($responseTemplate->fresh()->load('typeRequests:id,name'));
    }

    public function updateStatus(Request $request, ResponseTemplate $responseTemplate): JsonResponse
    {
        $this->assertSectorAccess($request, $responseTemplate->sector);
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
            'sector' => ['sometimes', 'in:CRADT,COORDENACAO'],
            'type_request_ids' => ['required', 'array', 'min:1'],
            'type_request_ids.*' => ['required', 'uuid', 'distinct', 'exists:type_requests,id'],
        ]);
    }

    private function allowedSectors(Request $request): array
    {
        return in_array($request->user()?->role, ['admin', 'cradt'], true)
            ? ['CRADT', 'COORDENACAO']
            : [$request->user()?->role === 'coordenacao' ? 'COORDENACAO' : 'CRADT'];
    }

    private function assertSectorAccess(Request $request, string $sector): void
    {
        abort_unless(in_array($sector, $this->allowedSectors($request), true), 403);
    }
}
