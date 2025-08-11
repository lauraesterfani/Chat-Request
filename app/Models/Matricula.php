<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Matricula extends Model
{
    use HasFactory;
    protected $table = 'matriculas';
    protected $primaryKey = 'id_matricula';
    protected $fillable = [
        'numero_matricula', 'periodo_ingresso', 'turno', 'status_matricula', 
        'id_aluno', 'id_campus', 'id_curso'
    ];
    

    // Uma matrícula pertence a um Aluno, um Campus e um Curso
    public function aluno(): BelongsTo { return $this->belongsTo(Aluno::class, 'id_aluno'); }
    public function campus(): BelongsTo { return $this->belongsTo(Campus::class, 'id_campus'); }
    public function curso(): BelongsTo { return $this->belongsTo(Curso::class, 'id_curso'); }
}