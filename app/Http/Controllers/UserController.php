<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;
use App\Http\Resources\UserCollection;
use App\Model\User;

class UserController extends Controller
{
    use Functions;
    /**
     * Lấy danh sách người dùng
     */
    public function getUserList() {
        $users =  new UserCollection(User::paginate(10));
        return $this->success('Danh sách người dùng', $users->response()->getData(true));
    }
}
