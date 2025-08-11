<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Enrollment;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cpf',
        'birthday',
        'phone',
        'role_id',
        'user_type', // permite mass assignment
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

    // Adiciona o Accessor para user_type
    public function getUserTypeAttribute(): string
    {
        $roleMapping = [
            1 => 'admin',
            2 => 'staff',
            3 => 'student',
        ];

        return $roleMapping[$this->attributes['role_id']] ?? 'unknown';
    }

    // Métodos necessários para JWTAuth
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relacionamento com Enrollment
    public function enrollments()
    {
        return $this->hasMany(
            Enrollment::class,
            'user_id',
            'id'
        );
    }
}
