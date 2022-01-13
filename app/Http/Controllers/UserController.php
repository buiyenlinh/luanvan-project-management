<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use App\Model\User;
use App\Model\Role;

class UserController extends Controller
{
    use Functions;

    /**
     * Kiểm tra quyền người dùng thao tác user
     */
    public function checkUserLogin($role_id) {
        $user_login = new UserResource(User::where('token', request()->bearerToken())->first());
        $role = Role::find($role_id);
        if (!$role) {
            return $this->error('Quyền tài khoản này không tồn tại');
        }
        if ($user_login->role->level >= $role->level) {
            return $this->error('Tài khoản không có quyền');
        }
    }

    /**
     * Kiểm tra tồn tại
     */
    public function checkExist($name, $value, $id = 0) {
        $count = 0;
        if ($id == 0) { // Kiểm tra khi thêm
            $count = count(User::where($name, $value)->get());
        } else { // Kiểm tra khi cập nhật
            $count = count(User::where('id', '!=', $id)->where($name, $value)->get());
        }
        
        return $count > 0;
    }

    /**
     * Lấy danh sách người dùng
     */
    public function getUserList() {
        $users = User::paginate(4);
        $data = UserResource::collection($users)->response()->getData();
        return $this->success('Danh sách người dùng', $data);
    }

    /**
     * Thêm người dùng
     */
    public function addUser(Request $request) {
        $this->checkUserLogin($request->role);
        if ($this->checkExist('username', $request->username)) {
            return $this->error('Tên đăng nhập đã tồn tại');
        }

        if ($this->checkExist('email', $request->email)) {
            return $this->error('Email đã tồn tại');
        }

        if ($this->checkExist('phone', $request->phone)) {
            return $this->error('Số điện thoại đã tồn tại');
        }

        if (!$request->username) {
            return $this->error('Tên đăng nhập là bắt buộc');
        } elseif (!$request->password) {
            return $this->error('Mật khẩu là bắt buộc');
        } elseif (!$request->email) {
            return $this->error('Email là bắt buộc');
        } elseif ($request->active != 0 && $request->active != 1) {
            return $this->error('Trạng thái là bắt buộc');
        } elseif ($request->role <= 0) {
            return $this->error('Quyền tài khoản là bắt buộc');
        }
        $birthday = 0;
        if ($request->has('birthday')) {
            $birthday = strtotime($request->birthday);
        }
        $avatar = '';
        if ($request->file('avatar')) {
            $avatar = $request->file('avatar')->store('public/images');
            $avatar = Storage::url($avatar);
        }

        $fullname = '';
        if ($request->fullname) {
            $fullname = $request->fullname;
        }
        $user = User::create([
            'username' => $request->username,
            'fullname' => $fullname,
            'password' => bcrypt($request->password),
            'active' => $request->active,
            'gender' => $request->gender,
            'role_id' => $request->role,
            'phone' => $request->phone,
            'email' => $request->email,
            'birthday' => $birthday,
            'avatar' => $avatar,
        ]);

        return $this->success('Tạo tài khoản thành công', $user);
    }

    /**
     * Cập nhật người dùng
     */
    public function updateUser(Request $request) {
        $this->checkUserLogin($request->role);
        
        if ($this->checkExist('username', $request->username, $request->id)) {
            return $this->error('Tên đăng nhập đã tồn tại');
        }

        if ($this->checkExist('email', $request->email, $request->id)) {
            return $this->error('Email đã tồn tại');
        }

        if ($this->checkExist('phone', $request->phone, $request->id)) {
            return $this->error('Số điện thoại đã tồn tại');
        }

        if (!$request->username) {
            return $this->error('Tên đăng nhập là bắt buộc');
        } elseif (!$request->email) {
            return $this->error('Email là bắt buộc');
        } elseif ($request->active != 0 && $request->active != 1) {
            return $this->error('Trạng thái là bắt buộc');
        } elseif ($request->role <= 0) {
            return $this->error('Quyền tài khoản là bắt buộc');
        }

        $user = User::find($request->id);
        if (!$user) {
            return $this->error('Vui lòng thử lại');
        }

        $password = $user->password;
        $avatar = $user->avatar;
        $birthday = $user->birthday;
        $fullname = $user->fullname;
        if ($request->has('password')) {
            $password = $request->password;
        }

        if ($request->fullname) {
            $fullname = $request->fullname;
        }

        if ($request->file('avatar')) {
            Storage::delete($avatar);
            $avatar = $request->file('avatar')->store('public/images');
            $avatar = Storage::url($avatar);
        }

        if ($request->has('birthday')) {
            $birthday = strtotime($request->birthday);
        }

        $user->update([
            'username' => $request->username,
            'fullname' => $fullname,
            'password' => bcrypt($password),
            'gender' => $request->gender,
            'active' => $request->active,
            'avatar' => $avatar,
            'birthday' => $birthday,
            'role_id' => $request->role,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return $this->success('Cập nhật thành công', $user);
    }
}
