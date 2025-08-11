<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject; // <<< 1. IMPORTE A INTERFACE

class Aluno extends Authenticatable implements JWTSubject // <<< 2. IMPLEMENTE A INTERFACE
{
    use Notifiable;

    protected $table = 'alunos';
    protected $primaryKey = 'id_aluno';
    protected $fillable = [
        'nome_completo', 'telefone', 'email', 'password',
        'cpf', 'identidade', 'orgao_expedidor', 'tipo_usuario'
    ];

    protected $hidden = [
        'password',
    ];

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'id_aluno');
    }

    // --- 3. ADICIONE ESTES DOIS MÉTODOS OBRIGATÓRIOS ---

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}