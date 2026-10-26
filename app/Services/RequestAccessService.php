<?php

namespace App\Services;

use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RequestAccessService
{
    private const REQUEST_CAPABILITIES = ['view_request', 'reply_request', 'review_document', 'forward_request', 'decide_request', 'use_templates', 'manage_templates'];

    /**
     * Returns effective capabilities for the signed-in identity. When a
     * request is supplied, abilities and scope must come from the same grant.
     * The API still performs the authoritative check for every operation.
     *
     * @return array<int, string>
     */
    public function capabilitiesFor(User|StaffAdmin $actor, ?RequestModel $request = null): array
    {
        if ($actor instanceof User) {
            return $actor->role === User::ROLE_STUDENT ? ['view_own_request', 'reply_own_request'] : [];
        }
        if (! $actor->isActive()) {
            return [];
        }

        if (in_array($actor->role, ['admin', 'cradt'], true) && ! $this->hasExplicitScopes($actor)) {
            $capabilities = self::REQUEST_CAPABILITIES;
            if ($actor->role === 'admin') {
                $capabilities[] = 'manage_access';
            }

            return $capabilities;
        }

        if (! $this->hasExplicitScopes($actor)) {
            return $actor->role === 'coordenacao' ? ['view_request', 'reply_request', 'review_document', 'use_templates'] : [];
        }

        $scopes = StaffAccessScope::query()
            ->where('staff_admin_id', $actor->id)
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($query) => $query->whereNotNull('campus_id')->orWhereNotNull('course_id')->orWhereNotNull('sector'));

        if ($request) {
            $scopes->where(fn ($query) => $query->whereNull('campus_id')->orWhere('campus_id', $request->campus_id))
                ->where(fn ($query) => $query->whereNull('course_id')->orWhere('course_id', $request->course_id))
                ->where(fn ($query) => $query->whereNull('sector')->orWhere('sector', $request->responsible_sector));
        }

        return $scopes->get(['abilities'])->flatMap(fn (StaffAccessScope $scope) => $scope->abilities ?? [])->unique()->values()->all();
    }

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
        if ($actor->role === 'coordenacao' && ! $this->hasExplicitScopes($actor)) {
            return $actor->course_id
                ? $query->where('course_id', $actor->course_id)->where('responsible_sector', 'COORDENACAO')
                : $query->whereRaw('1 = 0');
        }

        $scopes = StaffAccessScope::query()
            ->where('staff_admin_id', $actor->id)
            ->whereNull('revoked_at')
            ->where(fn ($scopeQuery) => $scopeQuery->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($scopeQuery) => $scopeQuery->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereJsonContains('abilities', 'view_request')
            ->where(fn ($scopeQuery) => $scopeQuery
                ->whereNotNull('campus_id')
                ->orWhereNotNull('course_id')
                ->orWhereNotNull('sector'))
            ->get(['campus_id', 'course_id', 'sector']);

        if ($scopes->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $requestQuery) use ($scopes): void {
            foreach ($scopes as $scope) {
                $requestQuery->orWhere(function (Builder $scopeQuery) use ($scope): void {
                    if ($scope->course_id) {
                        $scopeQuery->where('course_id', $scope->course_id);
                    }
                    if ($scope->campus_id) {
                        $scopeQuery->where('campus_id', $scope->campus_id);
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
        if (! $actor->isActive()) {
            return false;
        }
        if (in_array($actor->role, ['admin', 'cradt'], true) && ! $this->hasExplicitScopes($actor)) {
            return true;
        }
        if ($this->hasExplicitScopes($actor)) {
            return StaffAccessScope::where('staff_admin_id', $actor->id)
                ->whereNull('revoked_at')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->whereJsonContains('abilities', 'view_request')
                ->where(fn ($query) => $query->whereNotNull('campus_id')->orWhereNotNull('course_id')->orWhereNotNull('sector'))
                ->where(fn ($query) => $query->whereNull('campus_id')->orWhere('campus_id', $request->campus_id))
                ->where(fn ($query) => $query->whereNull('course_id')->orWhere('course_id', $this->requestCourseId($request)))
                ->where(fn ($query) => $query->whereNull('sector')->orWhere('sector', $request->responsible_sector))
                ->exists();
        }

        return $actor->role === 'coordenacao'
            && $actor->course_id !== null
            && $this->requestCourseId($request) === $actor->course_id
            && $request->responsible_sector === 'COORDENACAO';
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

    /** @return array<int, string> */
    public function templateSectors(StaffAdmin $actor, bool $forManagement = false): array
    {
        if (! $actor->isActive()) {
            return [];
        }
        if (in_array($actor->role, ['admin', 'cradt'], true) && ! $this->hasExplicitScopes($actor)) {
            return ['CRADT', 'COORDENACAO'];
        }
        if (! $this->hasExplicitScopes($actor)) {
            return $actor->role === 'coordenacao' ? ['COORDENACAO'] : [];
        }

        $ability = $forManagement ? 'manage_templates' : 'use_templates';
        $scopes = StaffAccessScope::query()
            ->where('staff_admin_id', $actor->id)
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($query) => $query->whereJsonContains('abilities', $ability)
                ->when(! $forManagement, fn ($query) => $query->orWhereJsonContains('abilities', 'manage_templates')))
            ->whereNotNull('sector')
            ->pluck('sector')
            ->unique()
            ->values()
            ->all();

        return array_values(array_intersect($scopes, ['CRADT', 'COORDENACAO']));
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
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereJsonContains('abilities', $ability)
            ->where(fn ($query) => $query->whereNotNull('campus_id')->orWhereNotNull('course_id')->orWhereNotNull('sector'))
            ->where(fn ($query) => $query->whereNull('campus_id')->orWhere('campus_id', $request->campus_id))
            ->where(fn ($query) => $query->whereNull('course_id')->orWhere('course_id', $this->requestCourseId($request)))
            ->where(fn ($query) => $query->whereNull('sector')->orWhere('sector', $request->responsible_sector))
            ->exists();
    }

    public function constrainCourse(Builder $query, string $courseId): Builder
    {
        // Never infer a historical course from a student's mutable current profile.
        return $query->where('course_id', $courseId);
    }

    private function requestCourseId(RequestModel $request): ?string
    {
        // Requests without an enrollment snapshot are intentionally unresolved.
        // A mutable profile must not silently grant access to historical records.
        return $request->course_id;
    }
}
