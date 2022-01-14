<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use App\Model\User;
use App\Model\Role;
use App\Model\Project;
use App\Model\GroupUser;

class UserController extends Controller
{
    use Functions;

    /**
     * Kiểm tra quyền người dùng thao tác user
     */
    public function checkUserLogin($role_id) {
        $role = Role::find($role_id);
        if (!$role) {
            return false;
        }
        if ($this->auth->role->level >= $role->level) {
            return false;
        }
        return true;
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
        $users = User::orderby('id', 'desc')->paginate(4);
        $data = UserResource::collection($users)->response()->getData();
        return $this->success('Danh sách người dùng', $data);
    }

    /**
     * Thêm người dùng
     */
    public function addUser(Request $request) {
        if (!$this->checkUserLogin($request->role)) {
            return $this->error('Tài khoản không có quyền');
        }

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
            $avatar = str_replace('public/', '', $avatar);
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

        return $this->success('Tạo tài khoản thành công');
    }

    /**
     * Cập nhật người dùng
     */
    public function updateUser(Request $request) {
        if (!$this->checkUserLogin($request->role)) {
            return $this->error('Tài khoản không có quyền');
        }
        
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
            if (!empty($avatar)) {
                Storage::disk('public')->delete($avatar);
            }

            $file = $request->file('avatar');
            $avatar = $file->store('public/images');
            $avatar = str_replace('public/', '', $avatar);
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

    /**
     * Xóa người dùng
     */
    public function deleteUser(Request $request, $id) {
        $user = User::find($id);
        if (!$user) {
            return $this->error('Người dùng không tồn tại');
        }
        if (!$this->checkUserLogin($user->role_id)) {
            return $this->error('Tài khoản không có quyền');
        }

        $count_project = Project::where('manager', $user->id)
            ->orwhere('created_by', $user->id)
            ->count();
        if ($count_project > 0) {
            return $this->error('Người dùng đã thuộc một dự án nào đó. Không thể xóa');
        }

        $count_group_user = GroupUser::where('user_id', $user->id)->count();
        if ($count_group_user > 0) {
            return $this->error('Người dùng đã thuộc một nhóm nào đó. Không thể xóa');
        }
        // Storage::delete($user->avatar);
        $user->delete();

        return $this->success('Xóa người dùng thành công');
    }
}
