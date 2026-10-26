<?php

namespace App\Services;

use App\Models\ServiceOpeningWindow;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ServiceAvailabilityService
{
    public function isOpen(string $typeId, User $student, ?string $selectedEnrollmentId = null): bool
    {
        $windows = ServiceOpeningWindow::query()
            ->where('type_request_id', $typeId)
            ->where('status', 'published');

        // An absent or unpublished calendar is not an implicit authorization
        // to submit. The institution must publish a global or scoped window.
        if (! $windows->exists()) {
            return false;
        }

        if ($selectedEnrollmentId === null && $student->enrollments()->where('status', 'active')->limit(2)->count() > 1) {
            // A global window does not depend on enrollment selection. Let the
            // request flow return its precise "choose an enrollment" error;
            // scoped windows still require a selected, verifiable context.
            return $windows
                ->whereNull('campus_id')
                ->whereNull('course_id')
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->exists();
        }

        try {
            $context = app(EnrollmentSnapshotService::class)->forStudent($student, $selectedEnrollmentId);
        } catch (ValidationException) {
            // Missing selection for a student with multiple active enrollments,
            // inactive enrollment, or another student's enrollment all fail closed.
            return false;
        }

        return $windows
            ->where(fn ($query) => $query->whereNull('campus_id')->orWhere('campus_id', $context['campus_id']))
            ->where(fn ($query) => $query->whereNull('course_id')->orWhere('course_id', $context['course_id']))
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->exists();
    }
}
