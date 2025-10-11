<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Importar a trait para UUIDs
use Illuminate\Database\Eloquent\Relations\HasMany; // Importar a classe para relações
use App\Models\Enrollment; // Importar o Model Enrollment para a relação

class User extends Authenticatable implements JWTSubject
{
    // Usamos HasUuids, HasFactory, e Notifiable. Removemos as propriedades manuais de UUID.
    use HasFactory, Notifiable, HasUuids; 

    // O Eloquent já sabe que a chave é string e não incrementa por causa do HasUuids, 
    // mas vamos manter para clareza, caso o HasUuids não esteja disponível em todas as versões.
    public $incrementing = false;
    protected $keyType = 'string';
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'cpf',
        'phone', 
        'user_type', 
        'birthday', 
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

    // Removido o método boot() manual, pois o HasUuids cuida da geração do ID.

    // --- JWTSubject interface methods ---

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    
    // --- Relações ---
    
    /**
     * Define a relação: Um Usuário tem Muitas Matrículas (Enrollments)
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'user_id'); 
    }
}