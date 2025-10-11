<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str; // Importar a classe Str para gerar o UUID

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    // --- CORREÇÃO UUID ---
    // Indica ao Eloquent que a chave primária não é um inteiro auto-incrementável
    public $incrementing = false;
    protected $keyType = 'string';
    // ----------------------

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', // Incluímos o ID para ser preenchido, seja pelo Controller ou pelo boot()
        'name',
        'email',
        'password',
        'cpf',
        'phone',        // <-- CAMPO ADICIONADO
        'user_type',    // <-- CAMPO ADICIONADO
        'birthday',     // <-- CAMPO ADICIONADO
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Método de inicialização do Model, usado para injetar o UUID
     * antes de criar o registro.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Se o ID não estiver definido (o que é o caso na maioria dos creates),
            // gera um UUID
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }


    // JWTSubject interface methods

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
