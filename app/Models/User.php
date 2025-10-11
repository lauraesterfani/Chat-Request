<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject; // CORREÇÃO CRÍTICA: Importação da interface JWT

/**
 * O modelo User implementa JWTSubject para ser compatível com Tymon\JWTAuth.
 * HasApiTokens (Sanctum) foi removido, pois está sendo usado JWT.
 */
class User extends Authenticatable implements JWTSubject // CORREÇÃO CRÍTICA: Implementa a interface
{
    // Removido HasApiTokens, pois estamos usando JWT Auth (Tymon)
    use HasFactory, Notifiable, HasUuids; 

    // --- Definição de Papéis (Roles) ---
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STUDENT = 'student';
    public const ROLE_STAFF = 'staff'; 

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
        'role',
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
        'birthday' => 'date', 
    ];

    // --- IMPLEMENTAÇÃO JWTSubject (MÉTODOS OBRIGATÓRIOS) ---

    /**
     * Retorna o identificador único para ser armazenado no payload do token.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Retorna um array de custom claims a serem adicionados ao token.
     * Adicionamos a 'role' aqui para uso no RoleMiddleware.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        // Adicionando a 'role' ao payload do token
        return [
            'user_id' => $this->id,
            'role' => $this->role,
        ];
    }
    
    // --- Métodos de Checagem de Papel (Role Checkers) ---
    
    /**
     * Checa se o usuário é um administrador.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
    
    /**
     * Checa se o usuário é um estudante.
     */
    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }
    
    /**
     * Checa se o usuário é um funcionário (staff).
     */
    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }
    
    // --- Relações ---
    
    /**
     * Define a relação: Um Usuário tem Muitas Matrículas (Enrollments)
     */
    public function enrollments(): HasMany
    {
        // NOTA: Certifique-se de que a classe Enrollment foi importada ou está no mesmo namespace,
        // ou inclua o 'use App\Models\Enrollment;' no topo do arquivo.
        return $this->hasMany(Enrollment::class, 'user_id'); 
    }
}
