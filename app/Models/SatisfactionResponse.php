<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SatisfactionResponse extends Model
{
    use HasUuids;

    protected $fillable = ['request_id', 'student_id', 'rating', 'comment'];
}
