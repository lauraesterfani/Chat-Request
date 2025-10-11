<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'document_id',
        'type',
        'read_at',
    ];

    /**
     * Relacionamento com o usuário remetente.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Relacionamento com o usuário destinatário.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Relacionamento com o documento anexado (necessário para o MessageController).
     */
    public function document()
    {
        // Certifique-se de que o modelo Document está importado ou existe no mesmo namespace se você o moveu.
        return $this->belongsTo(Document::class, 'document_id');
    }
}
