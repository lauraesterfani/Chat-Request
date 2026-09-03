<?php

namespace App\Services;

use App\Models\Request as RequestModel;
use Carbon\CarbonImmutable;

class SlaService
{
    public function indicator(RequestModel $request, ?CarbonImmutable $now = null): string
    {
        if (!$request->sla_resolution_due_at) return 'no_policy';
        if (in_array($request->status?->value, ['completed', 'canceled'], true)) {
            if (!$request->resolved_at) return 'canceled';
            return $request->resolved_at->lte($request->sla_resolution_due_at) ? 'completed_on_time' : 'completed_late';
        }
        $now ??= CarbonImmutable::now();
        if ($request->sla_resolution_due_at->lte($now)) return 'overdue';
        $near = (int) ($request->relationLoaded('slaPolicy') ? ($request->slaPolicy?->near_due_minutes ?? 120) : 120);
        return $request->sla_resolution_due_at->diffInMinutes($now) <= $near ? 'near_due' : 'on_time';
    }
}
