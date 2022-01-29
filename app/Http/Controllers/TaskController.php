<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;
use App\Model\Task;
use App\Model\Project;
use App\Model\Label;

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

        $tasks = Task::where('project_id', $project->id)->get();
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

        $project = Project::find($project_id);
        if (!$project_id) {
            return $this->error('Dự án này không tồn tại');
        }   

        $pre_task_check = Task::where('id', $pre_task_id)->where('project_id', $project_id)->count();

        if ($pre_task_check <= 0) return $this->error('Công việc tiên quyết không tồn tại');

        if (!$name) return $this->error('Tên công việc là bắt buộc');
        if (!$describe) $describe = '';
        if (!$result) $result = '';
        if (!$start_time) return $this->error('Thời gian bắt đầu là bắt buộc');

        if (!$end_time) return $this->error('Thời gian kết thúc là bắt buộc');
        else if ($delay_time < 0) return $this->error('Thời gian trì hoãn không hợp lệ');

        if (!$label_id) {
            $label_id = '';
        } else {
            if ($label_id < 0) return $this->error('Nhãn công việc không hợp lệ');
            else {
                $label_check = Label::find($label_id);
                if (!$label_check) return $this->error('Nhãn chọn không tồn tại');
            }
        }

        return $this->success('Thêm công việc thành công', []);
    }
}
