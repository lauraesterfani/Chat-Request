<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <--- Importante para vincular ao Curso
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids;

    // --- Definição de Papéis (Roles) ---
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STUDENT = 'student';
    public const ROLE_STAFF = 'staff';
    public const ROLE_CRADT = 'cradt'; // <--- Adicionado para suportar a Coordenação

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'cpf',
        'phone',
        'matricula',
        'role',
        'birthday',
        'enrollment_number',
        'course_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birthday' => 'date',
    ];

    /**
     * BOOT: Garante a criação do UUID antes de salvar no banco.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // --- JWT Methods ---

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'user_id' => $this->id,
            'role' => $this->role,
            'name' => $this->name, // Útil para exibir no frontend
            'email' => $this->email
        ];
    }

    // --- Verificadores de Papel (Role Checkers) ---

    public function isAdmin(): bool
    {
        // Considera tanto 'admin' quanto 'cradt' como administradores
        return in_array(strtolower($this->role), [self::ROLE_ADMIN, self::ROLE_CRADT]);
    }

    public function isStaff(): bool
    {
        return strtolower($this->role) === self::ROLE_STAFF;
    }

    public function isStudent(): bool
    {
        return strtolower($this->role) === self::ROLE_STUDENT;
    }

    // --- Relações ---

    /**
     * Relação: Usuário pertence a um Curso
     */
    public function course()
        {
    return $this->belongsTo(Course::class);
        }


    /**
     * Relação: Usuário tem muitos Requerimentos
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class, 'user_id');
    }

    /**
     * Relação: Usuário tem muitas Matrículas
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'user_id');
    }
}