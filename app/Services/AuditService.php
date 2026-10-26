<?php

namespace App\Services;

use App\Models\AuditRecord;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditService
{
    private const SENSITIVE_KEY_PARTS = [
        'password', 'passwd', 'passphrase', 'secret', 'token', 'jwt', 'authorization', 'bearer',
        'cookie', 'session', 'csrf', 'email', 'mailaddress', 'cpf', 'cnpj', 'matricula',
        'enrollment', 'phone', 'telephone', 'birth', 'address', 'name', 'identifier',
        'userid', 'studentid', 'staffadminid', 'health', 'medical', 'signature',
        'document', 'attachment', 'url', 'link',
        'filecontent', 'message', 'content', 'body', 'comment', 'description', 'justification',
        'signedurl', 'recoverycode', 'privatekey', 'apikey', 'credential',
    ];

    public function record(Request $request, string $action, string $resourceType, string|int|null $resourceId = null, array $metadata = [], string $outcome = 'success'): void
    {
        if (! in_array($outcome, ['success', 'failure', 'denied', 'unknown'], true)) {
            $outcome = 'unknown';
        }

        $correlationId = $request->attributes->get('request_correlation_id');
        if (! is_string($correlationId) || ! Str::isUuid($correlationId)) {
            $correlationId = (string) Str::uuid();
            $request->attributes->set('request_correlation_id', $correlationId);
        }

        $actor = auth('staff_admins')->user() ?? auth('api')->user();
        AuditRecord::create([
            'actor_type' => $actor instanceof User ? 'student' : ($actor instanceof StaffAdmin ? 'staff_admin' : null),
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'correlation_id' => $correlationId,
            'outcome' => $outcome,
            'metadata' => $this->sanitize($metadata),
            // IP collection stays disabled until an institutional purpose and retention are approved.
            'ip_address' => null,
            'created_at' => now(),
        ]);
    }

    private function sanitize(array $metadata): array
    {
        $safe = [];

        foreach ($metadata as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                continue;
            }

            if (is_array($value)) {
                $safe[$key] = $this->sanitize($value);

                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $safe[$key] = $value;

                continue;
            }

            if (! is_string($value) || $this->containsSensitiveValue($value)) {
                continue;
            }

            $safe[$key] = mb_substr(trim($value), 0, 512);
        }

        return $safe;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $key));

        foreach (self::SENSITIVE_KEY_PARTS as $sensitivePart) {
            if (str_contains($normalized, $sensitivePart)) {
                return true;
            }
        }

        return false;
    }

    private function containsSensitiveValue(string $value): bool
    {
        return preg_match('/\bBearer\s+\S+/i', $value) === 1
            || preg_match('/\beyJ[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\b/', $value) === 1
            || preg_match('/\bhttps?:\/\/\S+/i', $value) === 1
            || preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $value) === 1
            || preg_match('/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/', $value) === 1;
    }
}
