<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class HRStaff extends Authenticatable
{
    use Notifiable;

    protected $table = 'hr_staff';

    protected $fillable = [
        'name', 'username', 'email', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
    ];
}
