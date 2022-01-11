<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Model\Role;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'yl_users';
    protected $guarded = [];
    
    protected $hidden = [
        'password',
    ];

    /**
     * Hàm này Lấy Role của User dựa vào khóa chính - phụ
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
