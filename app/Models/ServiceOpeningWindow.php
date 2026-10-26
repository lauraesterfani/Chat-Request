<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceOpeningWindow extends Model
{
    use HasUuids;

    protected $fillable = ['type_request_id', 'campus_id', 'course_id', 'starts_at', 'ends_at', 'timezone', 'status', 'source_reference', 'timestamps_utc_normalized', 'updated_by'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'timestamps_utc_normalized' => 'boolean',
        ];
    }
}
