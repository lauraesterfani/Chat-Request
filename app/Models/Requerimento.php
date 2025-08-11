<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requerimento extends Model
{
    use HasFactory;
    protected $table      = 'requerimentos';
    protected $primaryKey = 'id_requerimento';
    protected $fillable   = [
        'protocolo', 'status', 'observacoes', 'data_finalizacao',
        'id_matricula', 'id_tipo_requerimento',
    ];

    public function matricula(): BelongsTo
    {return $this->belongsTo(Matricula::class, 'id_matricula');}
    public function tipoRequerimento(): BelongsTo
    {return $this->belongsTo(TipoRequerimento::class, 'id_tipo_requerimento');}

    public function anexos(): HasMany
    {
        return $this->hasMany(RequerimentoAnexo::class, 'id_requerimento');
    }
}
