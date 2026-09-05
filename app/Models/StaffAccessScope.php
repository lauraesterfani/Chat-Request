<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAccessScope extends Model
{
    protected $fillable = ['staff_admin_id', 'course_id', 'sector', 'abilities', 'expires_at', 'granted_by', 'reason'];

    protected $casts = ['abilities' => 'array', 'expires_at' => 'datetime'];

    public function active(): bool
    {
        return ! $this->expires_at || $this->expires_at->isFuture();
    }

    public function staffAdmin()
    {
        return $this->belongsTo(StaffAdmin::class);
    }
}
