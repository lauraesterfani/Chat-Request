<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['recipient_type','recipient_id','category','internal_enabled','email_enabled'];
    protected $casts = ['internal_enabled' => 'boolean', 'email_enabled' => 'boolean'];
}
