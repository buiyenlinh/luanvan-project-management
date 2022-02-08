<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;
use App\Model\Task;
use App\Model\Project;
use App\Model\Label;
use App\Model\Department;
use App\Model\DepartmentTask;
use App\Model\DepartmentTaskStatus;
use App\Model\DepartmentUser;

class TaskController extends Controller
{
    use Functions;
    /**
     * Danh sách công việc trong một dự án
     */
    private $project = null;

    public function taskInProject(Request $request, $project_id) {
        $project = Project::find($project_id);

        if (!$project) {
            return $this->error('Dự án không tồn tại');
        }
        // $db = Task::select('*');
        // $db::where('project_id', $project->id);

        // if ($this->isUser()) {
        //     $_department = DepartmentUser::where('user_id', $this->auth->id)->first()->department_id;
        //     return $_department;
        // }

        // Nếu user thì chỉ trả về các task có jobs của user này

        $tasks = Task::where('project_id', $project_id)->get();


        return $this->success('Danh sách công việc', $tasks);
    }

    /**
     * Tìm công việc theo tên
     */
    public function searchTaskName(Request $request, $project_id) {
        $project = Project::find($project_id);
        $name = $request->keyword;
        if (!$name) {
            return $this->success('Danh sách công việc tìm kiếm', []);
        }
        $tasks = Task::where('name', 'LIKE', '%' . $name . '%')->where('project_id', $project->id)->get();
        return $this->success('Danh sách công việc tìm kiếm', $tasks);
    }

    /**
     * Thêm công việc
     */
    public function add(Request $request, $project_id) {
        $name = $request->name;
        $describe = $request->describe;
        $result = $request->result;
        $start_time = $request->start_time;
        $end_time = $request->end_time;
        $delay_time = $request->delay_time;
        $label_id = $request->label_id;
        $pre_task_id = $request->pre_task_id;
        $department_id = $request->department_id;


        $project = Project::find($project_id);
        if (!$project) {
            return $this->error('Dự án này không tồn tại');
        }

        if (!$pre_task_id) {
            $pre_task_id = 0;
        } else {
            $pre_task_check = Task::where('id', $pre_task_id)->where('project_id', $project_id)->count();
            if ($pre_task_check <= 0) return $this->error('Công việc tiên quyết không tồn tại');
        }

        if (!$name) return $this->error('Tên công việc là bắt buộc');
        if (!$describe) $describe = '';
        if (!$result) $result = '';
        if (!$start_time) return $this->error('Thời gian bắt đầu là bắt buộc');
        if (!$end_time) return $this->error('Thời gian kết thúc là bắt buộc');
        if (!$delay_time) $delay_time = 0;
        if ($delay_time < 0) return $this->error('Thời gian trì hoãn không hợp lệ');

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        if ($start_time < $project->start_time) 
            return $this->error('Thời gian bắt đầu công việc phải sau thời gian bắt đầu của dự án');

        if ($start_time > $end_time) return $this->error('Thời gian bắt đầu phải trước thời gian kết thúc');
        if ($end_time + intval($delay_time) * 24 * 3600 > $project->end_time + intval($project->delay_time) * 24 * 3600) {
            return $this->error('Thời gian kết thúc công việc quá hạn thời gian dự án');
        }

        if (!$label_id) {
            $label_id = '';
        } else {
            if ($label_id < 0) return $this->error('Nhãn công việc không hợp lệ');
            else {
                $label_check = Label::find($label_id);
                if (!$label_check) return $this->error('Nhãn chọn không tồn tại');
            }
        }

        if (!$department_id) 
            return $this->error('Phân công cho phòng ban là bắt buộc');
        else {
            if ($department_id < 0) return $this->error('Phòng ban không hợp lệ');
            else {
                $department_check = Department::find($department_id);
                if (!$department_check) 
                    return $this->error('Phòng ban không tồn tại');
            }
        }

        $file = '';
        if ($request->file('file')) {
            $file = $request->file('file')->store('public/files');
            $file = str_replace('public/', '', $file);
        }

        $new_task = Task::create([
            'name' => $name,
            'describe' => $describe,
            'result' => $result,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'delay_time' => $delay_time,
            'label_id' => $label_id,
            'project_id' => $project_id,
            'pre_task_id' => $pre_task_id,
            'file' => $file
        ]);

        $department_task = DepartmentTask::create([
            'department_id' => $department_id,
            'task_id' => $new_task->id,
        ]);

        DepartmentTaskStatus::create([
            'content' => '',
            'status' => 0,
            'department_task_id' => $department_task->id
        ]);

        return $this->success('Thêm công việc thành công', []);
    }
}
