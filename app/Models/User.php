<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
  use HasFactory, Notifiable;

  protected $keyType = 'string';
  public $incrementing = false;

  protected $fillable = [
    'id',
    'name',
    'email',
    'password',
    'cpf',
    'birthday',
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

  public function enrollment()
  {
    return $this->hasMany(Enrollment::class);
  }
}
