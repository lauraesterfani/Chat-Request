<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAccessScope extends Model
{
    protected $fillable = ['staff_admin_id', 'campus_id', 'course_id', 'sector', 'abilities', 'starts_at', 'expires_at', 'revoked_at', 'granted_by', 'revoked_by', 'reason'];

    protected $casts = ['abilities' => 'array', 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function active(): bool
    {
        return ! $this->revoked_at
            && (! $this->starts_at || $this->starts_at->isPast() || $this->starts_at->equalTo(now()))
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function staffAdmin()
    {
        return $this->belongsTo(StaffAdmin::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
