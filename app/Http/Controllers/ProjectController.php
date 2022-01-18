<?php

namespace App\Http\Controllers;
use App\Http\Functions;
use App\Model\Project;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use Functions;

    /**
     * Kiểm tra trường tồn tại
     */
    public function checkExist($name, $value, $id = 0) {
        $count = 0;
        if ($id == 0) { // Kiểm tra khi thêm
            $count = Project::where($name, $value)->count();
        } else { // Kiểm tra khi cập nhật
            $count = Project::where('id', '!=', $id)->where($name, $value)->count();
        }
        return $count > 0;
    }

    /**
     * Danh sách dự án
     */
    public function listProject() {
        $db = Project::select('*');
        if ($this->isManager()) {
            $db->where('manager', $this->auth->id);
        } else if ($this->isUser()) {
            //Lấy những dự án mà nhân viên tham gia
        }

        $list = $db->orderBy('id','desc')->paginate(2);
        $data = ProjectResource::collection($list)->response()->getData();
        return $this->success('Danh sách dự án', $data);
    }

    /**
     * Thêm dự án
     */
    public function addProject(Request $request) {
        $name = $request->name;
        $start_time = $request->start_time;
        $end_time = $request->end_time;
        $delay_time = $request->delay_time;
        $active = $request->active;
        $manager = $request->manager;
        $describe = $request->describe;
        $created_by = $request->created_by;

        if (!$name) {
            return $this->error('Tên dự án là bắt buộc');
        } else if (!$start_time) {
            return $this->error('Thời gian bắt đầu là bắt buộc');
        } else if (!$end_time) {
            return $this->error('Thời gian kết thúc là bắt buộc');
        } else if ($start_time > $end_time) {
            return $this->error('Thời gian bắt đầu phải trước thời gian kết thúc');
        } else if ($active != 1 && $active != 0) {
            return $this->error('Trạng thái là bắt buộc');
        }else if ($delay_time < 0) {
            return $this->error('Thời gian trì hoãn không hợp lệ');
        }
        
        if ($this->checkExist('name', $name)) {
            return $this->error('Tên dự án đã tồn tại');
        }

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        Project::create([
            'name' => $name,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'delay_time' => $delay_time,
            'active' => $active,
            'manager' => $manager,
            'created_by' => $created_by,
            'describe' => $describe,
        ]);
        return $this->success('Thêm dự án thành công');
    }

    /**
     * Cập nhật dự án
     */
    public function updateProject(Request $request) {
        $name = $request->name;
        $start_time = $request->start_time;
        $end_time = $request->end_time;
        $delay_time = $request->delay_time;
        $active = $request->active;
        $manager = $request->manager;
        $describe = $request->describe;
        $created_by = $request->created_by;

        if (!$name) {
            return $this->error('Tên dự án là bắt buộc');
        } else if (!$start_time) {
            return $this->error('Thời gian bắt đầu là bắt buộc');
        } else if (!$end_time) {
            return $this->error('Thời gian kết thúc là bắt buộc');
        } else if ($start_time > $end_time) {
            return $this->error('Thời gian bắt đầu phải trước thời gian kết thúc');
        } else if ($active != 1 && $active != 0) {
            return $this->error('Trạng thái là bắt buộc');
        }else if ($delay_time < 0) {
            return $this->error('Thời gian trì hoãn không hợp lệ');
        }
        
        if ($this->checkExist('name', $name, $request->id)) {
            return $this->error('Tên dự án đã tồn tại');
        }

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        Project::find($request->id)->update([
            'name' => $name,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'delay_time' => $delay_time,
            'active' => $active,
            'manager' => $manager,
            'created_by' => $created_by,
            'describe' => $describe,
        ]);
        return $this->success('Cập nhật dự án thành công');
    } 


    /**
     * Xóa dự án
     */
    public function deleteProject(Request $request) {
        $id = $request->id;
        if (!$id) {
            return $this->error('Vui lòng thử lại');
        }
        
    }
}
