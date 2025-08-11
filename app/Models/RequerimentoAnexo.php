<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequerimentoAnexo extends Model
{
    use HasFactory;

    protected $table = 'requerimento_anexos';
    protected $primaryKey = 'id_anexo';
    protected $fillable = [
        'id_requerimento',
        'id_tipo_anexo',
        'caminho_arquivo',
        'nome_original',
    ];

    // Um anexo pertence a um requerimento
    public function requerimento(): BelongsTo
    {
        return $this->belongsTo(Requerimento::class, 'id_requerimento');
    }

    // Um anexo enviado é de um tipo específico
    public function tipoAnexo(): BelongsTo
    {
        return $this->belongsTo(TipoAnexo::class, 'id_tipo_anexo');
    }
}