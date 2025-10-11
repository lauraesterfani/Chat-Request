<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids; // IMPORTAÇÃO NECESSÁRIA
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa a Matrícula (Enrollment) de um Usuário.
 * Usa ID do tipo UUID como chave primária.
 */
class Enrollment extends Model
{
    use HasFactory, HasUuids; // AGORA INCLUI O TRAIT HasUuids

    // Configuração para usar UUID como chave primária (string)
    protected $keyType = 'string';
    // Desativa o auto-incremento
    public $incrementing = false;

    // Colunas que podem ser atribuídas em massa.
    protected $fillable = [
        'id', // Permite que o AuthController atribua o UUID gerado
        'user_id',
        'enrollment', // O número/código da matrícula
        'status', // e.g., 'ativo', 'inativo'
    ];
    
    /**
     * Relacionamento: Uma Matrícula pertence a um Usuário.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        // Usa a chave user_id para referenciar o UUID do modelo User.
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
