<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class StaffAdmin extends Authenticatable implements JWTSubject
{
    protected $table = 'staff_admins';
    protected $fillable = [
        'name',
        'email',
        'cpf',
        'phone',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    // Métodos exigidos pelo JWTSubject
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
