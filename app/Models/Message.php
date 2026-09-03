<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['request_id', 'sender_id', 'sender_type', 'content'];

    /**
     * Relacionamento com o usuário remetente.
     */
    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * Relacionamento com o usuário destinatário.
     */
    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }
}
