<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Aluno extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'alunos';
    protected $primaryKey = 'id_aluno';
    protected $fillable = [
        'nome_completo', 'telefone', 'email', 'password',
        'cpf', 'identidade', 'orgao_expedidor', 'tipo_usuario'
    ];

    protected $hidden = [ 'password' ];

    /**
     * Define a relação de que um Aluno pode ter várias Matrículas.
     * O nome do método deve ser 'matriculas' (plural).
     */

        public function matriculas()
    {
        return $this->hasMany(\App\Models\Matricula::class);
    }

    // public function matriculas(): HasMany
    // {
    //     return $this->hasMany(Matricula::class, 'id_aluno', 'id_aluno');
    // }
    

    // --- Métodos obrigatórios para JWTAuth ---
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}