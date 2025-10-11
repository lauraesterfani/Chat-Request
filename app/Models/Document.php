<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Importação essencial para tipagem
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type_document_id', // Adicionado: Campo usado pelo DocumentController
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];

    /**
     * Relacionamento: Um Documento pertence a um Usuário (o uploader).
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        // Define explicitamente as chaves 'user_id' e 'id' para funcionar com UUIDs.
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relacionamento: Um Documento pode ser anexado a UMA Mensagem.
     * @return HasOne
     */
    public function message(): HasOne
    {
        // A mensagem aponta para o ID deste documento.
        return $this->hasOne(Message::class, 'document_id', 'id');
    }
}
