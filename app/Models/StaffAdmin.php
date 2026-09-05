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
        'must_change_password',
        'course_id',
    ];

    protected $hidden = [
        'password',
    ];

    public function accessScopes()
    {
        return $this->hasMany(StaffAccessScope::class);
    }

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
