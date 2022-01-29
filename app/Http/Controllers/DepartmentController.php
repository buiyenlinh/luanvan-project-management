<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;      
use App\Http\Functions;
use App\Model\Department;
use App\Model\DepartmentUser;
use App\Model\DepartmentTask;
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
        
        if (!empty($department)) {
            $db->whereRaw('name LIKE "%' . $department . '%"');
        }

        if ($leader_id > 0) {
            $department_user = DepartmentUser::where('user_id', $leader_id)
                ->where('leader', 1)->get();
            $arr = [];
            foreach($department_user as $_de_user) {
                $arr[] = $_de_user['department_id'];
            }
            $db->whereIn('id', $arr);
        }

        if ($this->isUser()) {
            $department_user = DepartmentUser::where('user_id', $this->auth->id)->first();
            if ($department_user) {
                $db->where('id', $department_user->department_id);
            } else {
                $db->where('id', 0);
            }
        }

        $list = $db->orderBy('id','desc')->paginate(2);
        $data = DepartmentResource::collection($list)->response()->getData();
        return $this->success('Danh sách phòng ban', $data);
    }

    /**
     * Tạo phòng ban
     */
    public function create(Request $request) {
        $name = $request->name;
        $created_by = $request->created_by;
        $leader = $request->leader;
        $members = $request->members;
        if (!$name) {
            return $this->error('Tên phòng ban là bắt buộc');
        } else if ($created_by <= 0) {
            return $this->error('Vui lòng thử lại');
        } else if (empty($members)) {
            return $this->error('Vui lòng chọn thành viên nhóm');
        }

        $department = Department::create([
            'name' => $name,
            'created_by' => $created_by
        ]);

        // insert leader
        DepartmentUser::create([
            'user_id' => $leader,
            'department_id' => $department->id,
            'leader' => 1
        ]);

        foreach($members as $_member) {
            $department_user_count = DepartmentUser::where('user_id', $_member)->count();
            if ($department_user_count > 0) {
                return $this->error('Thành viên đã có phòng ban');
            }
            DepartmentUser::create([
                'user_id' => $_member,
                'department_id' => $department->id,
                'leader' => 0
            ]);
        }

        return $this->success('Tạo phòng ban thành công', $request);
    }

    /**
     * Cập nhật phòng ban
     */
    public function update(Request $request) {
        $name = $request->name;
        $created_by = $request->created_by;
        $leader = $request->leader;
        $members = $request->members;
        $id = $request->id;

        $department_update = Department::find($id);
        $members_update = DepartmentUser::where('department_id', $id)
            ->where('leader', 0)->get();

        if (!$department_update) return $this->error('Phòng ban này không tồn tại. Vui lòng thử lại');

        if (!$name) return $this->error('Tên phòng ban là bắt buộc');

        if (!$leader) return $this->error('Trưởng phòng ban là bắt buộc');

        if (count($members) == 0) return $this->error('Thành viên là bắt buộc');

        /* Trưởng phòng */
        $old_leader = DepartmentUser::where('department_id', $id)
            ->where('leader', 1)->first();
        $old_leader->update([
            'user_id' => $leader
        ]);

        // Xóa thành viên bị xóa ra khỏi phòng ban
        foreach($members_update as $_mem) {
            if (!in_array($_mem->user_id, $members)) {
                DepartmentUser::find($_mem->id)->delete();
            }
        }

        $members_update = DepartmentUser::where('department_id', $id)
            ->where('leader', 0)->get();

        $members_user_id = array();
        foreach($members_update as $_mem) { 
            $members_user_id[] = $_mem->user_id;
        }

        foreach($members as $_mem) {
            if (!in_array($_mem, $members_user_id)) {
                DepartmentUser::create([
                    'user_id' => $_mem,
                    'department_id' => $id,
                    'leader' => 0
                ]);
            }
        }

        Department::find($id)->update(['name' => $name]);

        return $this->success('Cập nhật phòng ban thành công');
    }


    /**
     * Xóa phòng ban
     */
    public function delete(Request $request) {
        $department = Department::find($request->id);
        if (!$department) {
            return $this->error('Vui lòng thử lại');
        }
        $department_task = DepartmentTask::where('department_id', $request->id)->count();
        if ($department_task > 0) {
            return $this->error('Phòng ban đã có dữ liệu không được xóa');
        }

        // Xóa các thành viên trong phòng ban
        $department_user = DepartmentUser::where('department_id', $department->id)->delete();
        $department->delete();

        return $this->success('Xóa phòng ban thành công');
    }


    /**
     * Tìm kiếm phòng ban
     */
    public function searchDepartment(Request $request) {
        $keyword = $request->keyword;
        if (!$keyword) {
            return $this->success('Danh sách tìm kiếm phòng ban', []);
        }

        $department = Department::where('name', 'LIKE', '%' . $keyword . '%')->get();
        return $this->success('Danh sách tìm kiếm phòng ban', $department);
    }
}
