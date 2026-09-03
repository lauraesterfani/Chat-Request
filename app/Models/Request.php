<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type_id',
        'subject',
        'description',
        'status',
        'observation',
        'protocol', // <--- OBRIGATÓRIO: Sem isso, o salvamento falha!
        'responsible_sector',
        'result',
        'conclusion_summary',
        'assigned_staff_id', 'priority', 'first_response_at', 'resolved_at', 'sla_policy_id', 'sla_policy_version', 'sla_first_response_due_at', 'sla_resolution_due_at',
    ];

    protected function casts(): array
    {
        return ['status' => RequestStatus::class, 'first_response_at' => 'datetime', 'resolved_at' => 'datetime', 'sla_first_response_due_at' => 'datetime', 'sla_resolution_due_at' => 'datetime'];
    }

    public function documents()
    {
        // Garante o uso da tabela pivô correta
        return $this->belongsToMany(Document::class, 'requests_documents', 'request_id', 'document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(TypeRequest::class, 'type_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function events()
    {
        return $this->hasMany(RequestEvent::class)->orderBy('created_at');
    }

    public function assignedStaff() { return $this->belongsTo(StaffAdmin::class, 'assigned_staff_id'); }
    public function slaPolicy() { return $this->belongsTo(SlaPolicy::class, 'sla_policy_id'); }
}
