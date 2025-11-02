<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne; // Adicionado para a relação Message

class Document extends Model
{
    use HasFactory, HasUuids; // Habilitado HasUuids

    public $incrementing = false;
    protected $keyType = 'string';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'type_document_id', // <--- CORRIGIDO: Nome final da coluna
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];
    
    // --- Relações ---
    
    /**
     * Relação com o tipo de documento (RG, CNH, etc.).
     * Aponta para TypeDocument (o conceito do documento).
     */
    public function typeDocument(): BelongsTo
    {
        // O Laravel assume 'type_document_id' por padrão, o que é perfeito!
        return $this->belongsTo(TypeDocument::class, 'type_document_id'); 
    }

    /**
     * Relacionamento: Um Documento pertence a um Usuário (o uploader).
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        // Usa as chaves padrão, que funcionam com UUIDs e sem explicitar se a convenção é seguida
        return $this->belongsTo(User::class, 'user_id');
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
