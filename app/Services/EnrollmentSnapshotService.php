<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EnrollmentSnapshotService
{
    /** @return array<int, array{id: string, enrollment: string, course_id: string, course_name: ?string}> */
    public function activeForStudent(User $student): array
    {
        $enrollments = $student->enrollments()->where('status', 'active')
            ->orderBy('enrollment')->get(['id', 'enrollment', 'course_id']);
        $courses = Course::whereIn('id', $enrollments->pluck('course_id'))
            ->pluck('name', 'id');

        return $enrollments->map(fn (Enrollment $enrollment) => [
            'id' => $enrollment->id,
            'enrollment' => $enrollment->enrollment,
            'course_id' => $enrollment->course_id,
            'course_name' => $courses->get($enrollment->course_id),
        ])->all();
    }

    /** @return array{course_id: ?string, enrollment_id: ?string} */
    public function forStudent(User $student, ?string $selectedEnrollmentId = null): array
    {
        $enrollments = $student->enrollments()->where('status', 'active')->get(['id', 'course_id']);
        if ($selectedEnrollmentId !== null) {
            $selected = $enrollments->firstWhere('id', $selectedEnrollmentId);
            if (! $selected) {
                throw ValidationException::withMessages(['enrollment_id' => 'A matrícula selecionada não está ativa ou não pertence a você.']);
            }

            return ['course_id' => $selected->course_id, 'enrollment_id' => $selected->id];
        }

        if ($enrollments->count() > 1) {
            throw ValidationException::withMessages(['enrollment_id' => 'Escolha a matrícula acadêmica para este requerimento.']);
        }
        $singleEnrollment = $enrollments->first();

        return [
            'course_id' => $singleEnrollment?->course_id ?? $student->course_id,
            'enrollment_id' => $singleEnrollment?->id,
        ];
    }
}
