<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// O nome da classe deve ser ServiceRequest
class ServiceRequest extends Model
{
    use HasFactory;

    /**
     * A tabela do banco de dados associada ao modelo.
     *
     * @var string
     */
    protected $table = 'requests';

    /**
     * Os atributos que são preenchíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'status',
        // Adicione aqui outros campos da sua tabela
    ];

    /**
     * Obtém o usuário que é dono da requisição.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
