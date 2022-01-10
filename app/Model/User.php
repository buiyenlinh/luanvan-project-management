<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'yl_users';
    protected $guarded = [];
    
    protected $hidden = [
        'password',
    ];
}
