<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;      
use App\Http\Functions;
use App\Model\Department;
use App\Model\DepartmentUser;
use App\Model\DepartmentTask;
use App\Model\User;
use App\Http\Resources\UserResource;
use App\Http\Resources\DepartmentResource;

class DepartmentController extends Controller
{
    use Functions;

    /**
     * Kiểm tra trường tồn tại
     */
    public function checkExist($name, $value, $id = 0) {
        $count = 0;
        if ($id == 0) { // Kiểm tra khi thêm
            $count = Department::where($name, $value)->count();
        } else { // Kiểm tra khi cập nhật
            $count = Department::where('id', '!=', $id)->where($name, $value)->count();
        }
        return $count > 0;
    }

    /**
     * Lấy danh sách
     */
    public function getList(Request $request) {
        $leader_id = $request->leader;
        $department = $request->department;
        $db = Department::select('*');
        
        if (!empty($department)) { // Tìm theo tên nhóm làm việc
            $db->whereRaw('name LIKE "%' . $department . '%"');
        }

        if ($leader_id > 0) { // Danh sách tìm theo leader
            $department_user = DepartmentUser::where('user_id', $leader_id)
                ->where('leader', 1)->get();
            $arr = [];
            foreach($department_user as $_de_user) {
                $arr[] = $_de_user['department_id'];
            }
            $db->whereIn('id', $arr);
        }

        // if ($this->isUser() || $this->isManager()) {
        if ($this->isUser()) {
            $department_user = DepartmentUser::where('user_id', $this->auth->id)->latest('id')->first();
            if ($department_user) {
                $db->where('id', $department_user->department_id);
            } else {
                $db->where('id', 0);
            }
        }

        $list = $db->orderBy('id','desc')->paginate(8);
        $data = DepartmentResource::collection($list)->response()->getData();
        return $this->success('Danh sách nhóm làm việc', $data);
    }

    /**
     * Tạo nhóm làm việc
     */
    public function create(Request $request) {
        $name = $request->name;
        $created_by = $request->created_by;
        $leader = $request->leader;
        $members = $request->members;
        if (!$name) {
            return $this->error('Tên nhóm làm việc là bắt buộc');
        } else if ($created_by <= 0) {
            return $this->error('Vui lòng thử lại');
        }

        $department_check = Department::where('name', $name)->first();
        if ($department_check) {
            return $this->error("Tên nhóm làm việc đã tồn tại");
        }

        $department = Department::create([
            'name' => $name,
            'created_by' => $created_by
        ]);

        // insert leader
        DepartmentUser::create([
            'user_id' => $leader,
            'department_id' => $department->id,
            'leader' => 1,
            'active_leader' => 1
        ]);

        $this->_sendRealtime([
            'name' => 'department',
            'notification' => [
                'title' => 'Thêm vào nhóm làm việc',
                'message' => 'Bạn vừa được phân làm trưởng nhóm ' . $name
            ]
        ], 'user' . $leader);

        $mem_emails = array();
        $_leader_user = User::find($leader);
        $mem_emails[] = $_leader_user->email;

        if ($members) {
            foreach($members as $_member) {
                $department_user_count = DepartmentUser::where('user_id', $_member)->count();
                if ($department_user_count > 0) {
                    return $this->error('Thành viên đã thuộc nhóm làm việc');
                }
                DepartmentUser::create([
                    'user_id' => $_member,
                    'department_id' => $department->id,
                    'leader' => 0,
                    'active_leader' => 0
                ]);

                // gửi thông báo cho các thành viên được thêm vào nhóm làm việc
                $this->_sendRealtime([
                    'name' => 'department',
                    'notification' => [
                        'title' => 'Thêm vào nhóm làm việc',
                        'message' => 'Bạn vừa được thêm vào nhóm làm việc ' . $name
                    ]
                ], 'user' . $_member);

                $_user = user::find($_member);
                if ($_user)
                    $mem_emails[] = $_user->email;
            }
        }

        /** Thông báo email cho leader & thành viên */
        $content_mail = '<div>Chào bạn!</div><div>Bạn vừa được thêm vào nhóm làm việc <b>'
        . $name . '</b></div><div>Cảm ơn!</div>';
        $this->_sendEmail($mem_emails, 'Được thêm vào nhóm làm việc', $content_mail);

        return $this->success('Tạo nhóm làm việc thành công', $request);
    }

    /**
     * Cập nhật nhóm làm việc
     */
    public function update(Request $request) {
        $leader = $request->leader;
        $id = $request->id;
        $new_members = $request->members;

        $department_update = Department::find($id);
        if (!$department_update) return $this->error('Nhóm làm việc không tồn tại.');

        if (!$leader) return $this->error('Trưởng nhóm làm việc là bắt buộc');

        /* Trưởng nhóm */
        $old_leader = DepartmentUser::where('department_id', $id)
            ->where('leader', 1)->where('active_leader', 1)->latest('id')->first();

        $user_send_email = array();
        if ($old_leader->user_id != $leader) { // Trưởng nhóm mới
            $old_leader->update([
                'active_leader' => 0
            ]); 

            // Tạo department_user cho trưởng nhóm mới
            DepartmentUser::create([
                'user_id' => $leader,
                'active_leader' => 1,
                'department_id' => $id,
                'leader' => 1   
            ]);

            $this->_sendRealtime([
                'name' => 'department',
                'notification' => [
                    'title' => 'Thêm vào nhóm làm việc',
                    'message' => 'Bạn vừa được phân làm trưởng nhóm làm việc ' . $department_update->name
                ]
            ], 'user' . $leader);

            $user_leader = User::find($leader);
            if ($user_leader && $user_leader->email)
                $user_send_email[] = $user_leader->email;
        }

        if ($new_members) {
            foreach ($new_members as $_member) {
                $user_check = User::find($_member);
                if ($user_check) {
                    DepartmentUser::create([
                        'user_id' => $_member,
                        'active_leader' => 0,
                        'department_id' => $id,
                        'leader' => 0
                    ]);

                    $this->_sendRealtime([
                        'name' => 'department',
                        'notification' => [
                            'title' => 'Thêm vào nhóm làm việc',
                            'message' => 'Bạn vừa được thêm vào nhóm làm việc ' . $department_update->name
                        ]
                    ], 'user' . $_member);

                    $user_send_email[] = $user_check->email;
                }
            }
        }

        /** Gửi email cho trưởng nhóm mới & thành viên mới */
        $content_mail = '<div>Chào bạn!</div><div>Bạn vừa được thêm vào nhóm làm việc <b>'
        . $department_update->name . '</b></div><div>Cảm ơn!</div>';
        $this->_sendEmail($user_send_email, 'Được thêm vào nhóm làm việc', $content_mail);

        return $this->success('Cập nhật nhóm làm việc thành công');
    }


    /**
     * Thêm thành viên mới
     */
    public function addNewMember(Request $request, $department_id) {
        $department = Department::find($department_id);
        if (!$department) return $this->error('Nhóm làm việc không tồn tại');

        $new_members = $request->new_members;
        if (!$new_members)
            return $this->error('Thành viên mới là bắt buộc');
        
        $mem_emails = array();
        foreach ($new_members as $_new_member) {
            $user_check = User::find($_new_member);
            if ($user_check) {
                DepartmentUser::create([
                    'user_id' => $_new_member,  
                    'active_leader' => 0,
                    'department_id' => $department_id
                ]);

                $this->_sendRealtime([
                    'name' => 'department',
                    'notification' => [
                        'title' => 'Thêm vào nhóm làm việc',
                        'message' => 'Bạn vừa được thêm vào nhóm làm việc ' . $department->name
                    ]
                ], 'user' . $_new_member);

                $_user = User::find($_new_member);
                if ($_user) 
                    $mem_emails[] = $_user->email;
            }
        }

        /** Thông báo email cho leader & thành viên */
        $content_mail = '<div>Chào bạn!</div><div>Bạn vừa được thêm vào nhóm làm việc <b>'
        . $department->name . '</b></div><div>Cảm ơn!</div>';
        $this->_sendEmail($mem_emails, 'Được thêm vào nhóm làm việc', $content_mail);

        return $this->success('Thêm thành viên mới thành công', []);
    }


    /**
     * Xóa nhóm làm việc
     */
    public function delete(Request $request) {
        $department = Department::find($request->id);
        if (!$department) {
            return $this->error('Vui lòng thử lại');
        }
        $department_task = DepartmentTask::where('department_id', $request->id)->count();
        if ($department_task > 0) {
            return $this->error('Nhóm làm việc đã được phân công công việc. Không được xóa');
        }

        // Xóa các thành viên trong nhóm làm việc
        $department_user = DepartmentUser::where('department_id', $department->id)->delete();
        $department->delete();

        return $this->success('Xóa nhóm làm việc thành công');
    }


    /**
     * Tìm kiếm nhóm làm việc
     */
    public function searchDepartment(Request $request) {
        $keyword = $request->keyword;
        $department = array();
        if (!$keyword) {
            if ($request->callfirst == true)
                $department = Department::all();
            else
            return $this->success('Danh sách tìm kiếm nhóm làm việc', []);
        } else {
            $department = Department::where('name', 'LIKE', '%' . $keyword . '%')->get();
        }
        return $this->success('Danh sách tìm kiếm nhóm làm việc', $department);
    }

    /**
     * Danh sách thành viên
     */
    public function members(Request $request, $department_id) {
        $department = Department::find($department_id);
        if (!$department) return $this->error('Nhóm làm việc không tồn tại');

        $department_user = DepartmentUser::where('department_id', $department_id);
        $ids = [];
        $data = [
            'list' => [],
            'leader' => 0
        ];

        if ($department_user->count()) {
            foreach ($department_user->get() as $_department_user) {
                if (($_department_user->leader == 1 && $_department_user->active_leader) || ($_department_user->leader == 0 && $_department_user->active_leader == 0)) {
                    $ids[] = $_department_user->user_id;
                    if ($_department_user->leader == 1) {
                        $data['leader'] = $_department_user->user_id;
                    }
                }
            }
        }

        $users = User::select('*');
        $users->whereIn('id', $ids);
        if ($request->keyword) {
            $users->whereRaw('(username LIKE "%' . $request->keyword . '%" OR fullname LIKE "%' . $request->keyword . '%")');
        }

        $list = UserResource::collection($users->orderby('id', 'desc')->get())->response()->getData();
        $data['list'] = $list;
        
        return $this->success('Danh sách thành viên nhóm làm việc', $data);
    }

    /**
     * Lấy thông tin nhóm làm việc
     */
    public function getInfoDepartment(Request $request, $department_id) {
        $department = Department::find($department_id);

        if (!$department)
            return $this->error('Nhóm làm việc không tồn tại');
        
        return $this->success('Thông tin nhóm làm việc', $department);
    }
}
