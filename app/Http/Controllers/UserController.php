<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use App\Model\User;
use App\Model\Role;
use App\Model\Chat;
use App\Model\Project;
use App\Model\ProjectStatus;
use App\Model\DepartmentUser;
use App\Model\Task;
use App\Model\DepartmentTask;
use App\Model\DepartmentTaskStatus;

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
            $count = User::where($name, $value)->count();
        } else { // Kiểm tra khi cập nhật
            $count = User::where('id', '!=', $id)->where($name, $value)->count();
        }
        
        return $count > 0;
    }

    /**
     * Lấy danh sách người dùng
     */
    public function getUserList(Request $request) {
        $keyword = $request->keyword;
        $active = $request->active;

        $db = User::select('*');
        if (!empty($keyword)) {
            $db->whereRaw('(username LIKE "%' . $keyword . '%" OR fullname LIKE "%' . $keyword . '%")');
        }

        if (in_array($active, [0,1])) {
            $db->where('active', $active);
        }

        $users = $db->orderby('id', 'desc')->paginate(10);
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

        if ($this->checkExist('username', $request->username)) {
            return $this->error('Tên đăng nhập đã tồn tại');
        }

        if ($this->checkExist('email', $request->email)) {
            return $this->error('Email đã tồn tại');
        }

        if ($this->checkExist('phone', $request->phone)) {
            return $this->error('Số điện thoại đã tồn tại');
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

        if ($request->email) {
            $_name = $fullname;
            if (!$fullname)
                $_name = $request->username;
            $content_mail = '<div>Hi ' . $_name . '!</div><div>Bạn vừa được cấp tài khoản vào hệ thống YL Quản lý dự án</div><div>Tên đăng nhập: <b>' . $request->username . '</b></div><div>Mật khẩu: <b>' . $request->password . '</b></div><div>Đừng quên tạo mật khẩu mới cho tài khoản của bạn!</div>';
            $this->_sendEmail($request->email, 'Cấp quyền vào hệ thống', $content_mail);
        }

        return $this->success('Tạo tài khoản thành công');
    }

    /**
     * Cập nhật người dùng
     */
    public function updateUser(Request $request) {
        if (!$this->checkUserLogin($request->role)) return $this->error('Tài khoản không có quyền');
        
        if ($this->checkExist('username', $request->username, $request->id)) return $this->error('Tên đăng nhập này đã tồn tại');

        if ($this->checkExist('email', $request->email, $request->id)) return $this->error('Email này đã tồn tại');

        if ($this->checkExist('phone', $request->phone, $request->id)) return $this->error('Số điện thoại này đã tồn tại');

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
        if (!$user) return $this->error('Vui lòng thử lại');

        $password = $user->password;
        $avatar = $user->avatar;
        $birthday = $user->birthday;
        $fullname = $user->fullname;
        if ($request->has('password')) $password = bcrypt($request->password);

        if ($request->fullname) $fullname = $request->fullname;

        if ($request->file('avatar')) {
            if (!empty($avatar)) {
                Storage::disk('public')->delete($avatar);
            }

            $file = $request->file('avatar');
            $avatar = $file->store('public/images');
            $avatar = str_replace('public/', '', $avatar);
        }

        if ($request->has('birthday')) $birthday = strtotime($request->birthday);

        if ($user->role_id != $request->role) {
            $project_count = Project::where('created_by', $request->id)
                ->orwhere('manager', $request->id)->count();
            if ($project_count > 0) {
                return $this->error('Tài khoản này hiện là quản trị hoặc là quản lý của một dự án nào đó. Không thể đổi quyền tài khoản');
            }

            $department_user = DepartmentUser::where('user_id', $request->id)
                ->where('leader', 1)->count();
            if ($department_user > 0) {
                return $this->error('Tài khoản này hiện là trưởng phòng của phòng ban nào đó. Vui lòng đổi trưởng phòng ban trước khi thay đổi quyền của tài khoản này');
            }
        }

        $user->update([
            'username' => $request->username,
            'fullname' => $fullname,
            'password' => $password,
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

    public function get_code($a, $b) {
        if ($a < $b) return $a . '_' . $b;
        return $b . '_' . $a;
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

        $count_chat = Chat::where('sender', $id)->count();
        if ($count_chat > 0) return $this->error('Người dùng này đã có dữ liệu trong website! Không thể xóa! Bạn có thể khóa tài khoản người dùng này!');

        $count_project = Project::where('manager', $user->id)
            ->orwhere('created_by', $user->id)
            ->count();
        if ($count_project > 0) {
            return $this->error('Người dùng đã thuộc một dự án nào đó. Không thể xóa');
        }

        $count_department_user = DepartmentUser::where('user_id', $user->id)->count();
        if ($count_department_user > 0) {
            return $this->error('Người dùng đã thuộc một phòng ban nào đó. Không thể xóa');
        }
        Storage::delete($user->avatar);

        $user->delete();

        return $this->success('Xóa người dùng thành công');
    }

    /**
     * Tìm kiếm quản lý
     */
    public function searchManager(Request $request) {
        $role = Role::where('level', 3)->first();   
        $keyword = $request->keyword;

        $list = User::select('*');
        if (!empty($keyword)) {
            $list->whereRaw('(username LIKE "%' . $keyword . '%" OR fullname LIKE "%' . $keyword . '%")');
        }
        $list->where('active', 1)->where('role_id', $role->id);

        return $this->success('Danh sách tìm kiếm quản lý dự án', $list->get());
    }

    /**
     * Tìm kiếm user
     */
    public function searchUser(Request $request) {
        $role = Role::where('level', 4)->first();   
        $keyword = $request->keyword;

        $list = User::select('*');
        if (!empty($keyword)) {
            $list->whereRaw('(username LIKE "%' . $keyword . '%" OR fullname LIKE "%' . $keyword . '%")');
        }
        $list->where('active', 1)->where('role_id', $role->id);

        return $this->success('Danh sách tìm kiếm thành viên', $list->get());
    }

    /**
     * Tìm kiếm user chưa có phòng ban
     */
    public function searchUserNotDepartment(Request $request) {
        $role = Role::where('level', 4)->first();   
        $keyword = $request->keyword;

        $list = User::select('*');
        if (!empty($keyword)) {
            $list->whereRaw('(username LIKE "%' . $keyword . '%" OR fullname LIKE "%' . $keyword . '%")');
        }
        $list->where('active', 1)->where('role_id', $role->id);

        $data = array();
        foreach($list->get() as $_list) {
            $department_user_old_leader = DepartmentUser::where('user_id', $_list->id)->count();
            $department_user = DepartmentUser::where('user_id', $_list->id)->where('leader', 0)->count();
            if ($department_user == 0 && $department_user_old_leader == 0) {
                $data[] = $_list;
            }
        }

        return $this->success('Danh sách tìm kiếm thành viên', $data);
    }

    /**
     * Cập nhật profile
     */
    public function updateProfile(Request $request) {
        if ($this->checkExist('username', $request->username, $this->auth->id)) {
            return $this->error('Tên đăng nhập đã tồn tại');
        }

        if ($this->checkExist('email', $request->email, $this->auth->id)) {
            return $this->error('Email đã tồn tại');
        }

        if ($this->checkExist('phone', $request->phone, $this->auth->id)) {
            return $this->error('Số điện thoại đã tồn tại');
        }

        $password = $this->auth->password;
        $birthday = $this->auth->birthday;
        $avatar = $this->auth->avatar;

        if ($request->has('password')) {
            $password = bcrypt($request->password);
        }
        if ($request->has('birthday')) {
            $birthday = strtotime($request->birthday);
        }

        if ($request->file('avatar')) {
            if (!empty($avatar)) {
                Storage::disk('public')->delete($avatar);
            }

            $file = $request->file('avatar');
            $avatar = $file->store('public/images');
            $avatar = str_replace('public/', '', $avatar);
        }

        $this->auth->update([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'password' => $password,
            'gender' => $request->gender,
            'birthday' => $birthday,
            'email' => $request->email,
            'phone' => $request->phone,
            'avatar' => $avatar,
        ]);

        return $this->success('Cập nhật thông tin thành công', $this->_createAuth($this->auth));
    }

    /**
     * Xóa ảnh đại diện
     */
    public function deleteAvatar() {
        $avatar = $this->auth->avatar;
        if (!empty($avatar)) {
            Storage::disk('public')->delete($avatar);
        }
        $this->auth->update(['avatar' => '']);
        return $this->success('Xóa ảnh đại diện thành công', $this->_createAuth($this->auth));
    }
}
