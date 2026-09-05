<?php

namespace App\Services;

use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\User;

class RequestAccessService
{
    public function canView(User|StaffAdmin $actor, RequestModel $request): bool
    {
        if ($actor instanceof User) {
            return $actor->role === User::ROLE_STUDENT && $request->user_id === $actor->getKey();
        }
        if (in_array($actor->role, ['admin', 'cradt'], true) && ! $this->hasExplicitScopes($actor)) {
            return true;
        }
        if ($this->hasExplicitScopes($actor)) {
            return StaffAccessScope::where('staff_admin_id', $actor->id)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->whereJsonContains('abilities', 'view_request')
                ->where(fn ($query) => $query->whereNull('course_id')->orWhere('course_id', $request->user?->course_id))
                ->where(fn ($query) => $query->whereNull('sector')->orWhere('sector', $request->responsible_sector))
                ->exists();
        }

        return $actor->role === 'coordenacao' && $request->user?->course_id === $actor->course_id;
    }

    public function canOperate(User|StaffAdmin $actor, RequestModel $request, string $ability = 'forward_request'): bool
    {
        return $actor instanceof StaffAdmin && in_array($actor->role, ['admin', 'cradt'], true) && $this->canView($actor, $request) && (! $this->hasExplicitScopes($actor) || $this->hasAbility($actor, $request, $ability));
    }

    public function canReply(User|StaffAdmin $actor, RequestModel $request): bool
    {
        if ($actor instanceof User) {
            return $this->canView($actor, $request);
        }

        return $this->canView($actor, $request) && (! $this->hasExplicitScopes($actor) || $this->hasAbility($actor, $request, 'reply_request'));
    }

    private function hasExplicitScopes(StaffAdmin $actor): bool
    {
        return StaffAccessScope::where('staff_admin_id', $actor->id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    private function hasAbility(StaffAdmin $actor, RequestModel $request, string $ability): bool
    {
        return StaffAccessScope::where('staff_admin_id', $actor->id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereJsonContains('abilities', $ability)
            ->where(fn ($query) => $query->whereNull('course_id')->orWhere('course_id', $request->user?->course_id))
            ->where(fn ($query) => $query->whereNull('sector')->orWhere('sector', $request->responsible_sector))
            ->exists();
    }
}
