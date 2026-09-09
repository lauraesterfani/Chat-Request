<?php

namespace App\Http\Controllers;

use App\Models\ServiceOpeningWindow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceOpeningWindowController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['type_request_id' => ['nullable', 'uuid', 'exists:type_requests,id']]);

        return response()->json(ServiceOpeningWindow::query()->when($data['type_request_id'] ?? null, fn ($query, $id) => $query->where('type_request_id', $id))->orderByDesc('starts_at')->get());
    }

    public function store(Request $request)
    {
        $window = ServiceOpeningWindow::create($this->validated($request) + ['updated_by' => auth('staff_admins')->id()]);

        return response()->json($window, 201);
    }

    public function update(Request $request, ServiceOpeningWindow $serviceOpeningWindow)
    {
        $serviceOpeningWindow->update($this->validated($request) + ['updated_by' => auth('staff_admins')->id()]);

        return response()->json($serviceOpeningWindow->fresh());
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type_request_id' => ['required', 'uuid', 'exists:type_requests,id'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'timezone' => ['required', 'timezone'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'source_reference' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
