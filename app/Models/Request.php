<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Request extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'id',
        'type_request_id',
        'enrollment_id',
        'protocol',
        'status',
        'observations',
        'due_date'
    ];

    // Relações
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function typeRequest()
    {
        return $this->belongsTo(TypeRequest::class, 'type_request_id');
    }
    
    public function aluno()
    {
        // O requerimento pertence ao aluno através da matrícula
        return $this->hasOneThrough(User::class, Enrollment::class, 
            'id', // Chave da Matrícula (Enrollment)
            'id', // Chave do Usuário (User)
            'enrollment_id', // Chave local da Requisição
            'user_id' // Chave da Matrícula para o Usuário
        );
    }

    // SCOPES PARA O DASHBOARDCONTROLLER

    /**
     * Scope para filtrar requerimentos por situação (status).
     * @param Builder $query
     * @param string $status
     * @return Builder
     */
    public function scopeSituacao(Builder $query, string $status): Builder
    {
        // Assume que o status é exatamente um dos valores do ENUM (Aberto, Em Análise, etc.)
        return $query->where('status', $status);
    }

    /**
     * Scope para filtrar requerimentos por ID do aluno (user_id).
     * @param Builder $query
     * @param string $userId
     * @return Builder
     */
    public function scopePorAluno(Builder $query, string $userId): Builder
    {
        // Filtra requerimentos cujas matrículas pertencem ao user_id fornecido
        return $query->whereHas('enrollment', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope para filtrar requerimentos por data de abertura (created_at).
     * @param Builder $query
     * @param string $dataInicio
     * @param string|null $dataFim
     * @return Builder
     */
    public function scopeDataAbertura(Builder $query, string $dataInicio, ?string $dataFim = null): Builder
    {
        $query->whereDate('created_at', '>=', $dataInicio);
        
        if ($dataFim) {
            $query->whereDate('created_at', '<=', $dataFim);
        }

        return $query;
    }
    
    /**
     * Scope para encontrar requerimentos que estão vencidos.
     * @param Builder $query
     * @return Builder
     */
    public function scopeVencidos(Builder $query): Builder
    {
        // Um requerimento é vencido se tiver uma due_date (prazo) no passado e não estiver concluído ou cancelado
        return $query->where('due_date', '<', now())
                     ->whereNotIn('status', ['Concluído', 'Cancelado']);
    }
}