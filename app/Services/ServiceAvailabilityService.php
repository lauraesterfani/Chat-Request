<?php

namespace App\Services;

use App\Models\ServiceOpeningWindow;
use App\Models\User;

class ServiceAvailabilityService
{
    public function isOpen(string $typeId, User $student): bool
    {
        $windows = ServiceOpeningWindow::query()->where('type_request_id', $typeId)->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('course_id')->orWhere('course_id', $student->course_id));

        if (! $windows->exists()) {
            return true;
        }

        return $windows->where('starts_at', '<=', now())->where('ends_at', '>=', now())->exists();
    }
}
