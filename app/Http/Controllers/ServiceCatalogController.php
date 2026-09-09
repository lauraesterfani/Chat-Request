<?php

namespace App\Http\Controllers;

use App\Models\ServiceCatalogEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceCatalogController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:80'], 'category' => ['nullable', 'string', 'max:100']]);
        $entries = ServiceCatalogEntry::query()->with('typeRequest:id,name,description,requires_document,document_instructions')
            ->where('status', 'published')
            ->when($data['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($data['q'] ?? null, function ($query, $term): void {
                $query->whereHas('typeRequest', fn ($types) => $types->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"));
            })->orderBy('category')->orderBy('created_at')->get();

        return response()->json($entries->map(fn (ServiceCatalogEntry $entry) => $this->publicEntry($entry)));
    }

    public function adminIndex()
    {
        return response()->json(ServiceCatalogEntry::with('typeRequest:id,name')->orderByDesc('updated_at')->get());
    }

    public function store(Request $request)
    {
        $entry = ServiceCatalogEntry::create($this->validated($request) + ['updated_by' => auth('staff_admins')->id()]);

        return response()->json($entry->load('typeRequest:id,name'), 201);
    }

    public function update(Request $request, ServiceCatalogEntry $serviceCatalogEntry)
    {
        $values = $this->validated($request);
        $values['updated_by'] = auth('staff_admins')->id();
        $values['version'] = $serviceCatalogEntry->version + 1;
        if (($values['status'] ?? $serviceCatalogEntry->status) === 'published' && ! $serviceCatalogEntry->published_at) {
            $values['published_at'] = now();
        }
        $serviceCatalogEntry->update($values);

        return response()->json($serviceCatalogEntry->fresh()->load('typeRequest:id,name'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type_request_id' => ['required', 'uuid', Rule::unique('service_catalog_entries', 'type_request_id')->ignore($request->route('serviceCatalogEntry'))],
            'category' => ['nullable', 'string', 'max:100'],
            'audience' => ['nullable', 'string', 'max:120'],
            'channel' => ['required', Rule::in(['digital', 'external'])],
            'channel_instructions' => ['nullable', 'string', 'max:3000', 'required_if:channel,external'],
            'responsible_sector' => ['nullable', 'string', 'max:80'],
            'normative_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'review', 'published', 'inactive'])],
        ]);
    }

    private function publicEntry(ServiceCatalogEntry $entry): array
    {
        $type = $entry->typeRequest;

        return [
            'id' => $entry->id, 'name' => $type->name, 'description' => $type->description,
            'category' => $entry->category, 'audience' => $entry->audience, 'channel' => $entry->channel,
            'channel_instructions' => $entry->channel_instructions, 'responsible_sector' => $entry->responsible_sector,
            'documentation' => ['required' => $type->requires_document, 'instructions' => $type->document_instructions],
            'version' => $entry->version, 'published_at' => $entry->published_at?->toIso8601String(),
            'institutional_status' => 'published',
        ];
    }
}
