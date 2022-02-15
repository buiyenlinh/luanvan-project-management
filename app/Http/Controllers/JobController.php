<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Model\Role;
use App\Model\User;
use App\Model\Job;
use App\Model\Task;
use App\Model\Project;
use App\Model\DepartmentTask;
use App\Model\DepartmentUser;
use App\Model\DepartmentUserJob;
use App\Model\DepartmentUserJobStatus;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;


use App\Http\Functions;

class JobController extends Controller
{
    use Functions;

    /**
     * Lấy thông tin dự án & công việc của nhiệm vụ
     */
    public function getInfo(Request $request, $id, $task_id) {
        $project = Project::find($id);
        if (!$project) 
            return $this->error('Dự án này không tồn tại');
        
        $task = Task::find($task_id);
        if (!$task) 
            return $this->error('Công việc không tồn tại');

        if ($id != $task->project_id)
            return $this->error('Công việc ' . $task->name . ' không thuộc dự án ' . $project->name);

        $data = [
            'project' => new ProjectResource($project),
            'task' => new TaskResource($task)
        ];
        return $this->success('Thông tin', $data);
        
    }

    /**
     * Tìm kiếm user để phân công job
     */
    public function searchUserMember(Request $request, $id, $task_id) {
        $project = Project::find($id);
        if (!$project) 
            return $this->error('Dự án này không tồn tại');
        
        $task = Task::find($task_id);
        if (!$task) 
            return $this->error('Công việc không tồn tại');

        if ($id != $task->project_id)
            return $this->error('Công việc ' . $task->name . ' không thuộc dự án ' . $project->name);

        $department_task = DepartmentTask::where('task_id', $task_id)->first();    
        $department_id = '';
        if ($department_task) $department_id = $department_task->department_id;

        $department_user = DepartmentUser::where('department_id', $department_id)->get();
        $user_ids = array();
        if ($department_user) {
            foreach ($department_user as $_department_user) {
                $user_ids[] = $_department_user->user_id;
            }
        }

        $role = Role::where('level', 4)->first();   
        $keyword = $request->keyword; // fullname or username

        $list = User::select('*');
        if (!empty($keyword)) {
            $list->whereRaw('(username LIKE "%' . $keyword . '%" OR fullname LIKE "%' . $keyword . '%")');
        }

        $list->whereIn('id', $user_ids);
        $list->where('active', 1)->where('role_id', $role->id);

        return $this->success('Danh sách thành viên', $list->get());
    }

    /**
     * Tìm job cùng task
     */
    public function searchJob(Request $request, $id, $task_id) {
        $project = Project::find($id);
        if (!$project) 
            return $this->error('Dự án này không tồn tại');
        
        $task = Task::find($task_id);
        if (!$task) 
            return $this->error('Công việc không tồn tại');

        if ($id != $task->project_id)
            return $this->error('Công việc ' . $task->name . ' không thuộc dự án ' . $project->name);

        $name = $request->keyword;
        if (!$name) 
            return $this->success('Danh sách công việc tìm kiếm', []);

        $jobs = Job::where('name', 'LIKE', '%' . $name . '%')->where('task_id', $task_id)->get();
        return $this->success('Danh sách nhiệm vụ tìm kiếm', $jobs);
    }

    /**
     * Thêm nhiệm vụ
     */
    public function add(Request $request, $id, $task_id) {
        $project = Project::find($id);
        if (!$project) 
            return $this->error('Dự án này không tồn tại');
        
        $task = Task::find($task_id);
        if (!$task) 
            return $this->error('Công việc không tồn tại');

        if ($id != $task->project_id)
            return $this->error('Công việc ' . $task->name . ' không thuộc dự án ' . $project->name);

        $name = $request->name;
        $start_time = $request->start_time;
        $end_time = $request->end_time;
        $user_id = $request->user_id;
        $content = $request->content;
        $pre_job_ids = $request->pre_job_ids;

        if (!$name) return $this->error('Tên nhiệm vụ là bắt buộc');
        if (!$start_time) return $this->error('Thời gian bắt đầu là bắt buộc');
        if (!$end_time) return $this->error('Thời gian kết thúc là bắt buộc');
        if (!$user_id) return $this->error('Người dùng là bắt buộc');
        if (!$content) $content = '';

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        if ($start_time > $end_time) return $this->error('Thời gian bắt đầu phải trước thời gian kết thúc');

        if ($start_time < $task->start_time) 
            return $this->error('Thời gian bắt đầu nhiệm vụ phải sau thời gian bắt đầu của công việc');

        if ($end_time > $task->end_time)
            return $this->error('Thời gian kết thúc nhiệm vụ quá hạn thời gian công việc');
        
        $user = User::find($user_id);
        if (!$user || !$user->active) return $this->error('Thành viên được phân công không tồn tại hoặc đã bị khóa');

        // Department User
        $department_user = DepartmentUser::where('user_id', $user_id)->first();
        if (!$department_user) return $this->error('Thành viên chưa có phòng ban');
        $department_task = DepartmentTask::where('task_id', $task_id)->first();
        if (!$department_task) return $this->error('Vui lòng thử lại');

        if ($department_user->department_id != $department_task->department_id)
            return $this->error('Thành viên không thuộc phòng ban được phân công');

        if (is_array($pre_job_ids)) {
            foreach ($pre_job_ids as $_pre_job_id) {
                $pre_job_check = Job::where('id', $_pre_job_id)
                    ->where('task_id', $task_id)
                    ->count();
                if ($pre_job_check <= 0)
                    return $this->error('Có một nhiệm vụ tiên quyết không tồn tại trong công việc');
            }
        }

        $file = '';
        if ($request->file('file')) {
            $file = $request->file('file')->store('public/files');
            $file = str_replace('public/', '', $file);
        }

        $new_job = Job::create([
            'name' => $name,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'content' => $content,
            'delay_time' => 0,
            'file' => $file,
            'task_id' => $task_id
        ]);

        /** Thêm nhiệm vụ tiên quyết */
        if (is_array($pre_job_ids)) {
            foreach ($pre_job_ids as $_pre_job_id) {
                PreJob::create([
                    'job_id' => $new_job->id,
                    'pre_job_id' => $_pre_job_id
                ]);
            }
        }

        if ($new_job) {
            $department_user_job = DepartmentUserJob::create([
                'department_user_id' => $department_user->id,
                'job_id' => $new_job->id
            ]);
        }

        if ($department_user_job) {
            DepartmentUserJobStatus::create([
                'content' => '',
                'status' => 0,
                'department_user_job_id' => $department_user_job->id
            ]);
        }

        return $this->success("Thêm nhiệm vụ thành công", []);
    }
}
