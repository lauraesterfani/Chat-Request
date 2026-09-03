<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    protected $fillable = ['name', 'version', 'status', 'type_request_id', 'first_response_minutes', 'resolution_minutes', 'near_due_minutes', 'counting_mode', 'timezone', 'justification', 'created_by', 'activated_at'];
    protected $casts = ['activated_at' => 'datetime'];
    public function typeRequest() { return $this->belongsTo(TypeRequest::class, 'type_request_id'); }
}
