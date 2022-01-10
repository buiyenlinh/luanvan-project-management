<?php

use Illuminate\Database\Seeder;
use App\Model\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new User();
        $user->fullname = 'Bùi Yến Linh';
        $user->username = 'linhlinh';
        $user->email = 'linhb1805782@student.ctu.edu.vn';
        $user->phone = '098754321';
        $user->active = 1;
        $user->password = bcrypt('linhlinh@123');
        $user->role_id = 1;
        $user->token = "";
        $user->save();
    }
}
