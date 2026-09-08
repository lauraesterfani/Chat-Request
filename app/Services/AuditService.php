<?php

namespace App\Services;

use App\Models\AuditRecord;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Http\Request;

class AuditService
{
    public function record(Request $request, string $action, string $resourceType, string|int|null $resourceId = null, array $metadata = []): void
    {
        $actor = auth('staff_admins')->user() ?? auth('api')->user();
        AuditRecord::create(['actor_type' => $actor instanceof User ? 'student' : ($actor instanceof StaffAdmin ? 'staff_admin' : null), 'actor_id' => $actor?->getKey(), 'action' => $action, 'resource_type' => $resourceType, 'resource_id' => $resourceId, 'metadata' => $this->sanitize($metadata), 'ip_address' => $request->ip(), 'created_at' => now()]);
    }

    private function sanitize(array $metadata): array
    {
        $blocked = ['password', 'token', 'authorization', 'cpf', 'email', 'content', 'message', 'document', 'attachment'];

        return collect($metadata)->reject(fn ($value, $key) => in_array(strtolower((string) $key), $blocked, true))->map(fn ($value) => is_array($value) ? $this->sanitize($value) : $value)->all();
    }
}
