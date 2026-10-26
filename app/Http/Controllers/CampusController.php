<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Course;
use App\Models\StaffAdmin;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampusController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->mayManageCampuses(), 403);

        return response()->json(Campus::query()->withCount('courses')->orderBy('name')->paginate(100));
    }

    public function store(Request $request)
    {
        abort_unless($this->mayManageCampuses(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:campuses,code'],
        ]);

        $campus = DB::transaction(function () use ($request, $data) {
            $campus = Campus::create([...$data, 'is_active' => true]);
            app(AuditService::class)->record($request, 'campus_created', 'campus', $campus->id, ['code' => $campus->code]);

            return $campus;
        });

        return response()->json($campus, 201);
    }

    public function update(Request $request, Campus $campus)
    {
        abort_unless($this->mayManageCampuses(), 403);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'code' => ['sometimes', 'required', 'string', 'max:40', 'alpha_dash', Rule::unique('campuses', 'code')->ignore($campus->id)],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $campus, $data): void {
            $campus->update($data);
            app(AuditService::class)->record($request, 'campus_updated', 'campus', $campus->id, ['changed_fields' => array_keys($data)]);
        });

        return response()->json($campus->fresh());
    }

    public function assignCourse(Request $request, Course $course)
    {
        abort_unless($this->mayManageCampuses(), 403);
        $data = $request->validate([
            'campus_id' => ['nullable', 'uuid', 'exists:campuses,id'],
        ]);
        $campus = ! empty($data['campus_id']) ? Campus::findOrFail($data['campus_id']) : null;
        if ($campus && ! $campus->is_active) {
            return response()->json(['message' => 'Não é possível associar o curso a um campus inativo.'], 422);
        }

        DB::transaction(function () use ($request, $course, $campus): void {
            $course->update(['campus_id' => $campus?->id]);
            app(AuditService::class)->record($request, 'course_campus_assigned', 'course', $course->id, ['campus_id' => $campus?->id]);
        });

        return response()->json($course->fresh()->load('campus:id,name,code'));
    }

    private function mayManageCampuses(): bool
    {
        $actor = auth('staff_admins')->user();

        return $actor instanceof StaffAdmin && $actor->isActive() && $actor->role === 'admin' && ! $actor->accessScopes()->exists();
    }
}
