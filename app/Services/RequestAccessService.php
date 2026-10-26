<?php

namespace App\Services;

use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RequestAccessService
{
    /**
     * Applies the same effective scope used by individual request access before
     * an aggregate is calculated. This prevents a scoped operator from seeing
     * institutional totals through the metrics endpoint.
     */
    public function constrainVisibleRequests(Builder $query, StaffAdmin $actor): Builder
    {
        if (in_array($actor->role, ['admin', 'cradt'], true) && ! $this->hasExplicitScopes($actor)) {
            return $query;
        }

        $scopes = StaffAccessScope::query()
            ->where('staff_admin_id', $actor->id)
            ->where(fn ($scopeQuery) => $scopeQuery->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereJsonContains('abilities', 'view_request')
            ->get(['course_id', 'sector']);

        if ($scopes->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $requestQuery) use ($scopes): void {
            foreach ($scopes as $scope) {
                $requestQuery->orWhere(function (Builder $scopeQuery) use ($scope): void {
                    if ($scope->course_id) {
                        $this->constrainCourse($scopeQuery, $scope->course_id);
                    }
                    if ($scope->sector) {
                        $scopeQuery->where('responsible_sector', $scope->sector);
                    }
                });
            }
        });
    }

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
                ->where(fn ($query) => $query->whereNull('course_id')->orWhere('course_id', $this->requestCourseId($request)))
                ->where(fn ($query) => $query->whereNull('sector')->orWhere('sector', $request->responsible_sector))
                ->exists();
        }

        return $actor->role === 'coordenacao' && $actor->course_id !== null && $this->requestCourseId($request) === $actor->course_id;
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
        // A grant remains an explicit restriction after expiry. Treating only
        // active grants as explicit would silently restore global admin access
        // as soon as the last temporary grant expired.
        return StaffAccessScope::where('staff_admin_id', $actor->id)->exists();
    }

    private function hasAbility(StaffAdmin $actor, RequestModel $request, string $ability): bool
    {
        return StaffAccessScope::where('staff_admin_id', $actor->id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereJsonContains('abilities', $ability)
            ->where(fn ($query) => $query->whereNull('course_id')->orWhere('course_id', $this->requestCourseId($request)))
            ->where(fn ($query) => $query->whereNull('sector')->orWhere('sector', $request->responsible_sector))
            ->exists();
    }

    public function constrainCourse(Builder $query, string $courseId): Builder
    {
        return $query->where(fn (Builder $requests) => $requests
            ->where('course_id', $courseId)
            ->orWhere(fn (Builder $legacy) => $legacy->whereNull('course_id')
                ->whereHas('user', fn (Builder $users) => $users->where('course_id', $courseId))));
    }

    private function requestCourseId(RequestModel $request): ?string
    {
        // Pedidos antigos não tinham snapshot: o fallback mantém o acesso
        // legado, sem inventar qual curso pertencia ao pedido histórico.
        return $request->course_id ?? $request->user?->course_id;
    }
}
