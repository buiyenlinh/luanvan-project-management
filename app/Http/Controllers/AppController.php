<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Functions;
use App\Http\Resources\UserLoginResource;

use App\Model\User;
use App\Model\Role;
use App\Model\PasswordReset;

class AppController extends Controller
{
    use Functions;

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
            'auth' => null,
        ];

        $token = $request->header('User-Token');
        if ($token) {
            $user = User::where('token', $token)->first();
            if ($user) {
                $this->user = $user->toArray();
                $data['auth'] = $this->_createAuth($user);
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
            return $this->error('Tên đăng nhập là bắt buộc');
        } elseif (!$password) {
            return $this->error('Mật khẩu là bắt buộc');
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
            'auth' => $this->_createAuth($user)
        ];

        return $this->success('Đăng nhập thành công', $data);
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request) {
        $user = User::where('token', $request->bearerToken())->first();
        if (!$user) {
            return $this->error('Vui lòng thử lại');
        }

        $user->update(['token' => '']);
        return $this->success("Đăng xuất thành công");
    }

    /**
     * Quên mật khẩu
     */
    public function forgetPassword(Request $request) {
        $random_code = Str::random(6);
        $user = User::where('email', $request->email)->latest('id')->first();
        if (!$user) return $this->error('Email không tồn tại trong website');

        $name = $user->fullname;
        if (!$name) $name = $user->username;
        $content_mail = '<div>Chào ' . $name . '</div><div>Bạn đã yêu cầu thay đổi mật khẩu. Vui lòng nhập mã xác thực bên dưới</div><div style="margin: 10px 0px 30px 0px"><b style="font-size: 25px; border: 1px solid #333; padding: 6px 10px">' . $random_code . '</b></div>';

        PasswordReset::create([
            'email' => $request->email,
            'code' => $random_code,
        ]);

        $this->_sendEmail($request->email, 'Xác thực quên mật khẩu', $content_mail);
        return $this->success('Đã gửi mã xác thực đến email của bạn', []);
    }

    /**
     * Thực hiện thay đổi pass
     */
    public function changePassword(Request $request) {
        $pass_reset = PasswordReset::where('email', $request->email)
            ->where('code', $request->code)->latest('id')->first();

        if (!$pass_reset)
            return $this->error('Mã xác thực không đúng');
        
        $time_now = strtotime(date("H:i:s, d-m-Y"));
        $created_at = strtotime($pass_reset->created_at->format('H:i:s, d-m-Y'));

        $limit = 5 * 60;
        if ($time_now - $created_at > $limit) {
            $pass_reset->delete();
            return $this->error('Mã xác thực đã hết hạn');
        }

        $user = User::where('username', $request->username)->where('email', $request->email)->latest('id')->first();
        if (!$user)
            return $this->error('Tên đăng nhập và email không khớp');
        
        $user->update([
            'password' => bcrypt($request->password)
        ]);

        $pass_reset->delete();

        return $this->success('Thay đổi mật khẩu thành công', []);
    }

}
