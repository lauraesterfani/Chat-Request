<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditRecord extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['actor_type', 'actor_id', 'action', 'resource_type', 'resource_id', 'correlation_id', 'outcome', 'metadata', 'ip_address', 'created_at'];

    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Audit records are append-only and cannot be updated.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Audit records are append-only and cannot be deleted.');
        });
    }
}
