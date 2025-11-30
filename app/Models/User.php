<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Enrollment; 
use Illuminate\Support\Str; 

/**
 * O modelo User implementa JWTSubject para ser compatível com Tymon\JWTAuth.
 */
class User extends Authenticatable implements JWTSubject
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
        'matricula',    // 🔥 CORREÇÃO: Adicionado para Mass Assignment
        'course_id',    // 🔥 CORREÇÃO: Adicionado para Mass Assignment
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
        // Garante que a comparação ignore maiúsculas/minúsculas
        return strtolower($this->role) === self::ROLE_ADMIN;
    }

    /**
     * Checa se o usuário é um membro do staff.
     */
    public function isStaff(): bool
    {
        return strtolower($this->role) === self::ROLE_STAFF;
    }

    /**
     * Checa se o usuário é um estudante.
     */
    public function isStudent(): bool
    {
        return strtolower($this->role) === self::ROLE_STUDENT; 
    }
    
    // --- Relações ---
    
    /**
     * Define a relação: Um Usuário tem Muitas Matrículas (Enrollments)
     */
    public function enrollments(): HasMany
    {
        // Usa a classe importada Enrollment
        return $this->hasMany(Enrollment::class, 'user_id'); 
    }
}