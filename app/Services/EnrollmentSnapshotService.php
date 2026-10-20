<?php

namespace App\Services;

use App\Models\User;

class EnrollmentSnapshotService
{
    /** @return array{course_id: ?string, enrollment_id: ?string} */
    public function forStudent(User $student): array
    {
        // O curso principal do perfil é o vínculo usado pelo fluxo atual. Só
        // associamos uma matrícula quando há uma correspondência inequívoca.
        $courseId = $student->course_id;
        $enrollments = $student->enrollments()
            ->where('status', 'active')
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->limit(2)
            ->get(['id', 'course_id']);

        if (! $courseId && $enrollments->count() === 1) {
            $courseId = $enrollments->first()->course_id;
        }

        return [
            'course_id' => $courseId,
            'enrollment_id' => $enrollments->count() === 1 ? $enrollments->first()->id : null,
        ];
    }
}
