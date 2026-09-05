<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NotificationRecord extends Model
{
    use HasUuids;

    public const CATEGORIES = ['message', 'status', 'request', 'document', 'workflow'];

    protected $table = 'notification_records';

    protected $fillable = ['recipient_type', 'recipient_id', 'request_id', 'category', 'title', 'body', 'link', 'channel', 'read_at', 'email_status', 'idempotency_key'];

    protected $casts = ['read_at' => 'datetime'];

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
