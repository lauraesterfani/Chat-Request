<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\User; 

class Enrollment extends Model
{
    use HasUuids;
    
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['enrollment', 'status', 'user_id', 'course_id']; 

    const FIXED_COURSES = [

        '8e6a2b9f-d0e5-4c1a-8b3d-7f4c5e0d9b1a' => 'Bacharelado em Administração',
        'c7d3f2e1-a0b9-4c8d-7e6f-5d4c3b2a1e0f' => 'Tecnologia em Sistemas para Internet',
        'e843b022-772c-4903-a4e2-d44a1e9411f1' => 'Tecnologia em Gestão da Qualidade',
        '83f5e1a3-2c1b-4d7f-a96e-0f8a7c6d5b4e' => 'Técnico em Logística',
        'c1d4e7a2-f6b9-4a0e-9c8d-7b6a5f4e3d2c' => 'Técnico em Informática para Internet',
    ];
    
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // Acessor que simula o relacionamento 'course' (usado pelo DashboardController)
    public function getCourseAttribute()
    {
        $courseName = self::FIXED_COURSES[$this->course_id] ?? 'Curso Desconhecido';

        
        return (object) [
            'id' => $this->course_id,
            'name' => $courseName
        ];
    }
}
