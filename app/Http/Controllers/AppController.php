<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Functions;

use App\Model\User;
use App\Model\Role;

class AppController extends Controller
{
    use Functions;

    private $auth = null;

    public function __contructor() {
        $token = request()->bearerToken();
        if ($token) {
            $user = User::where('token', $token)->first();
            if ($user) {
                $this->auth = $user->toArray();
            }
        }
    }

    public function index() {
        $data = [
            'page_title' => 'Quản lý dự án',
            'logo' => '',
            'favicon' => '/favicon.png'
        ];

        return view('index', $data);
    }

    public function getConfig(Request $request) {
        $data = [
            'config' => [],
            'auth' => null
        ];

        $token = $request->header('User-Token');
        if ($token) {
            $user = User::where('token', $token)->first();
            if ($user) {
                $this->user = $user->toArray();
                $data['auth'] = $user->toArray();
            }
        }

        return $this->success('', $data);
    }

    /**
     * Login
     */
    public function login(Request $request) {
        $username = $request->username;
        $password = $request->password;

        if (!$username) {
            return $this->error('');
        } elseif (!$password) {
            return $this->error('');
        }

        $user = User::where('username', $username)->first();
        if (!$user) {
            return $this->error('Tài khoản không tồn tại');
        } elseif (!Hash::check($password, $user->password)) {
            return $this->error('Mật khẩu không đúng');
        } elseif (!$user->active) {
            return $this->error('Tài khoản đã bị khóa');
        }

        $token = 'Yl' . $user->id . Str::random(80);
        $user->update([
            'token' => $token
        ]);

        $this->auth = $user->toArray();

        $data = [
            'auth' => $user->toArray()
        ];

        return $this->success('Đăng nhập thành công', $data);
    }
}
