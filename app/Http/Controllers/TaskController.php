<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;
use App\Model\Task;
use App\Model\Project;
use App\Model\Job;
use App\Model\Label;
use App\Model\PreTask;
use App\Model\Department;
use App\Model\DepartmentTask;
use App\Model\DepartmentTaskStatus;
use App\Model\DepartmentUser;
use App\Model\DepartmentUserJob;
use App\Http\Resources\TaskResource;

use Illuminate\Support\Facades\Storage;

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
        $db = Task::select('*');
        $db->where('project_id', $project->id);

        if ($request->name) {
            $db->whereRaw('name LIKE "%' . $request->name . '%"');
        }

        if ($request->department_id) {
            $department_task = DepartmentTask::where('department_id', $request->department_id)->get();
            $task_ids = array();
            if ($department_task) {
                foreach ($department_task as $_department_task) {
                    $task_ids[] = $_department_task->task_id;
                }
                $db->whereIn('id', $task_ids);
            }
        }

        if ($this->isUser()) {
            $_department = DepartmentUser::where('user_id', $this->auth->id)->first();
            if ($_department) {
                $task_ids = array();
                $department_user_job = DepartmentUserJob::where('department_user_id', $_department->id);
                foreach ($department_user_job->get() as $_dep_user_job) {
                    $task_ids[] = Job::find($_dep_user_job->job_id)->task_id;
                }

                if (count($task_ids) > 0) {
                    $db->whereIn('id', $task_ids);
                } else {
                    $db->where('id', 0);
                }
            }
        }

        $tasks = TaskResource::collection($db->orderBy('id','desc')->get())->response()->getData();
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
        $label_id = $request->label_id;
        $pre_task_ids = $request->pre_task_ids;
        $department_id = $request->department_id;

        $project = Project::find($project_id);
        if (!$project) {
            return $this->error('Dự án này không tồn tại');
        }
        
        if ($pre_task_ids) {
            foreach ($pre_task_ids as $_pre_task_id) {
                $pre_task_check = Task::where('id', $_pre_task_id)
                    ->where('project_id', $project_id)
                    ->count();
                if ($pre_task_check <= 0)
                    return $this->error('Có một công việc tiên quyết không tồn tại trong dự án');
            }
        }

        if (!$name) return $this->error('Tên công việc là bắt buộc');

        $checkNameExist = Task::where('name', $name)->first();
        if ($checkNameExist) return $this->error('Tên công việc đã tồn tại');

        if (!$describe) $describe = '';
        if (!$result) $result = '';
        if (!$start_time) return $this->error('Thời gian bắt đầu là bắt buộc');
        if (!$end_time) return $this->error('Thời gian kết thúc là bắt buộc');

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        if ($start_time < $project->start_time) 
            return $this->error('Thời gian bắt đầu công việc phải sau thời gian bắt đầu của dự án');

        if ($start_time > $end_time) return $this->error('Thời gian bắt đầu phải trước thời gian kết thúc');
        if ($end_time > $project->end_time) {
            return $this->error('Thời gian kết thúc công việc quá hạn thời gian dự án');
        }

        if (!$label_id) {
            $label_id = 0;
        } else {
            $label_check = Label::find($label_id);
            if (!$label_check) return $this->error('Nhãn chọn không tồn tại');
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
            'delay_time' => 0,
            'label_id' => $label_id,
            'project_id' => $project_id,
            'file' => $file
        ]);

        $department_task = DepartmentTask::create([
            'department_id' => $department_id,
            'task_id' => $new_task->id,
        ]);

        /** Thêm công việc tiên quyết */
        if ($pre_task_ids)
        foreach ($pre_task_ids as $_pre_task_id) {
            PreTask::create([
                'task_id' => $new_task->id,
                'pre_task_id' => $_pre_task_id
            ]);
        }

        DepartmentTaskStatus::create([
            'content' => '',
            'status' => 0,
            'department_task_id' => $department_task->id
        ]);

        return $this->success('Thêm công việc thành công', []);
    }

    /**
     * Cập nhật công việc
     */
    public function update(Request $request, $project_id) {
        $name = $request->name;
        $describe = $request->describe;
        $result = $request->result;
        $start_time = $request->start_time;
        $end_time = $request->end_time;
        $label_id = $request->label_id;
        $pre_task_ids = $request->pre_task_ids;
        $department_id = $request->department_id;
        $id = $request->id;

        $project = Project::find($project_id);
        if (!$project) {
            return $this->error('Dự án này không tồn tại');
        }

        $task = Task::find($id);
        if (!$task) return $this->error('Công việc cập nhật không tồn tại');

        if ($task->project_id != $project_id) return $this->error('Công việc không nằm trong dự án');

        if ($pre_task_ids) {
            foreach ($pre_task_ids as $_pre_task_id) {
                $task_check = Task::find($_pre_task_id);
                if (!$task_check) 
                    return $this->error('Có một công việc tiên quyết không tồn tại ');
                if ($project_id != $task_check->project_id) 
                    return $this->error('Có một công việc tiên quyết không nằm trong dự án');
            }
        }

        $department_task_check = DepartmentTask::where('task_id', $id)->first();

        if (!$label_id) {
            $label_id = 0;
        } else {
            $label_check = Label::find($label_id);
            if (!$label_check) return $this->error('Nhãn chọn không tồn tại');
        }

        if (!$name) return $this->error('Tên công việc là bắt buộc');

        $checkNameExist = Task::where('name', $name)->where('id', '!=', $id)->first();
        if ($checkNameExist) return $this->error('Tên công việc đã tồn tại');

        if (!$describe) $describe = '';
        if (!$result) $result = '';
        if (!$start_time) return $this->error('Thời gian bắt đầu là bắt buộc');
        if (!$end_time) return $this->error('Thời gian kết thúc là bắt buộc');

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        if ($start_time < $project->start_time) 
            return $this->error('Thời gian bắt đầu công việc phải sau thời gian bắt đầu của dự án');

        if ($start_time > $end_time) return $this->error('Thời gian bắt đầu phải trước thời gian kết thúc');
        if ($end_time > $project->end_time) {
            return $this->error('Thời gian kết thúc công việc quá hạn thời gian dự án');
        }

        if (!$label_id) {
            $label_id = 0;
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

        $file = $task->file;
        if ($request->file('file')) {
            if (!empty($file)) {
                Storage::disk('public')->delete($file);
            }

            $file = $request->file('file')->store('public/files');
            $file = str_replace('public/', '', $file);
        }


        if ($pre_task_ids) {
            // Lấy danh sách công việc tiên quyết cũ
            $pre_task_list_old = PreTask::where('task_id', $id)->get();
            // Xóa công việc tiên quyết đã bỏ
            foreach ($pre_task_list_old as $_pre_task_list_old) {
                if (!in_array($_pre_task_list_old->pre_task_id, $pre_task_ids)) {
                    PreTask::find($_pre_task_list_old->id)->delete();
                }
            }

            // Lấy lại danh sách sau khi xóa
            $pre_task_list_old = PreTask::where('task_id', $id)->get();
            $pre_task_ids_old = array();
            foreach ($pre_task_list_old as $_pre_task_list_old) {
                $pre_task_ids_old[] = $_pre_task_list_old->pre_task_id;
            }
            foreach ($pre_task_ids as $_pre_task_id) {
                if (!in_array($_pre_task_id, $pre_task_ids_old)) {
                    PreTask::create([
                        'task_id' => $id,
                        'pre_task_id' => $_pre_task_id
                    ]);
                }
            }

        } else {
            // Xóa hết các công việc tiên quyết
            PreTask::where('task_id', $id)->delete();
        }
        

        Task::find($id)->update([
            'name' => $name,
            'describe' => $describe,
            'result' => $result,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'label_id' => $label_id,
            'project_id' => $project_id,
            'file' => $file
        ]);

        if ($department_id != $department_task_check->department_id) {
            DepartmentTask::where('department_id', $department_task_check->department_id)
                ->where('task_id', $id)->update([
                'department_id' => $department_id,
                'task_id' => $id,
            ]);
        }

        return $this->success('Cập nhật công việc thành công', []);
    }

    public function delete(Request $request, $project_id) {
        $id = $request->id_task;
        $project = Project::find($project_id);
        if (!$project) {
            return $this->error('Dự án này không tồn tại');
        }

        $task = Task::find($id);
        if (!$task) return $this->error('Công việc cập nhật không tồn tại');

        if ($task->project_id != $project_id) return $this->error('Công việc không nằm trong dự án');

        /** Kiểm tra công việc này có nhiệm vụ nào hay không */
        $jobs = Job::where('task_id', $id)->count();
        if ($jobs > 0) return $this->error('Công việc này đã phân công nhiệm vụ. Không thể xóa');

        $department_task = DepartmentTask::where('task_id', $id)->first();
        $department_task_status = DepartmentTaskStatus::where('department_task_id', $department_task->id)->delete();
        $department_task->delete();

        if (!empty($task->file)) {
            Storage::disk('public')->delete($task->file);
        }

        $pre_tasks = PreTask::where('task_id', $id)->delete();
        $task->delete();
        return $this->success('Xóa công việc thành công', []);
    }


    /** Xóa file đính kèm */
    public function deleteFile(Request $request, $project_id) {
        $id = $request->id_task;
        $task = Task::find($id);
        if ($task->project_id != $project_id) 
            return $this->error('Công việc không thuộc dự án. Vui lòng thử lại');
        if (!$task) return $this->error('Công việc không tồn tại');

        Storage::disk('public')->delete($task->file);

        $task->update([
            'file' => ''
        ]);
        return $this->success('Xóa tệp đính kèm thành công');
    }
}
