<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Enrollment;

class User extends Authenticatable implements JWTSubject
{
  use HasFactory, Notifiable;

  protected $fillable = [
    'name',
    'email',
    'password',
    'cpf',
    'phone',
    'user_type',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  public function getJWTIdentifier()
  {
    return $this->getKey();
  }

  public function getJWTCustomClaims()
  {
    return [];
  }

  public function enrollments()
  {
    return $this->hasMany(
      Enrollment::class,
      'user_id',
      'id'
    );
  }
}
