<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ServiceOpeningWindow;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceOpeningWindowController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['type_request_id' => ['nullable', 'uuid', 'exists:type_requests,id']]);

        return response()->json(ServiceOpeningWindow::query()->when($data['type_request_id'] ?? null, fn ($query, $id) => $query->where('type_request_id', $id))->orderByDesc('starts_at')->get());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['timestamps_utc_normalized'] = $this->hasExplicitOffset($request->input('starts_at'))
            && $this->hasExplicitOffset($request->input('ends_at'));
        $window = ServiceOpeningWindow::create($data + ['updated_by' => auth('staff_admins')->id()]);

        return response()->json($window, 201);
    }

    public function update(Request $request, ServiceOpeningWindow $serviceOpeningWindow)
    {
        $this->assertLegacyTimestampUnchanged($request, $serviceOpeningWindow);
        $serviceOpeningWindow->update($this->validated($request) + ['updated_by' => auth('staff_admins')->id()]);

        return response()->json($serviceOpeningWindow->fresh());
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type_request_id' => ['required', 'uuid', 'exists:type_requests,id'],
            'campus_id' => ['nullable', 'uuid', 'exists:campuses,id'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'timezone' => ['required', 'timezone'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'source_reference' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($data['campus_id']) && ! empty($data['course_id']) && ! Course::query()
            ->whereKey($data['course_id'])
            ->where('campus_id', $data['campus_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'course_id' => 'O curso selecionado não pertence ao campus informado.',
            ]);
        }

        if ($data['status'] === 'published' && trim((string) ($data['source_reference'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'source_reference' => 'Informe a referência aprovada antes de publicar a janela.',
            ]);
        }

        if ($data['status'] === 'published') {
            $startsAt = $this->normalizeTimestamp($data['starts_at']);
            $endsAt = $this->normalizeTimestamp($data['ends_at']);
            $conflict = ServiceOpeningWindow::query()
                ->where('type_request_id', $data['type_request_id'])
                ->where('status', 'published')
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->when(
                    isset($data['campus_id']),
                    fn ($query) => $query->where('campus_id', $data['campus_id']),
                    fn ($query) => $query->whereNull('campus_id')
                )
                ->when(
                    isset($data['course_id']),
                    fn ($query) => $query->where('course_id', $data['course_id']),
                    fn ($query) => $query->whereNull('course_id')
                )
                ->when($request->route('serviceOpeningWindow'), fn ($query, $window) => $query->where('id', '!=', $window->id))
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Esta janela se sobrepõe a outra janela publicada para o mesmo serviço, campus e curso.',
                ]);
            }
        }

        $data['starts_at'] = $this->normalizeTimestamp($data['starts_at']);
        $data['ends_at'] = $this->normalizeTimestamp($data['ends_at']);

        return $data;
    }

    private function normalizeTimestamp(string $value): string|CarbonImmutable
    {
        // Keep legacy offset-less values untouched; their historical timezone
        // cannot be inferred safely. Offset-aware API values are canonicalized
        // to UTC before DATETIME persistence.
        if ($this->hasExplicitOffset($value)) {
            return CarbonImmutable::parse($value)->utc();
        }

        return $value;
    }

    private function hasExplicitOffset(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})$/i', $value) === 1;
    }

    private function assertLegacyTimestampUnchanged(Request $request, ServiceOpeningWindow $window): void
    {
        if ($window->timestamps_utc_normalized) {
            return;
        }

        $datesUnchanged = $request->input('starts_at') === $window->getRawOriginal('starts_at')
            && $request->input('ends_at') === $window->getRawOriginal('ends_at');
        $timezoneUnchanged = $request->input('timezone') === $window->getRawOriginal('timezone');

        if (! $datesUnchanged) {
            throw ValidationException::withMessages([
                'starts_at' => 'As datas desta janela antiga não podem ser alteradas até que o fuso histórico seja confirmado.',
                'ends_at' => 'As datas desta janela antiga não podem ser alteradas até que o fuso histórico seja confirmado.',
            ]);
        }

        if (! $timezoneUnchanged) {
            throw ValidationException::withMessages([
                'timezone' => 'O fuso desta janela antiga não pode ser alterado até que o fuso histórico seja confirmado.',
            ]);
        }
    }
}
