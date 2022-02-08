<?php

namespace App\Http\Controllers;
use App\Http\Functions;
use App\Model\Project;
use App\Model\Task;
use App\Model\Job;
use App\Model\Department;
use App\Model\DepartmentTask;
use App\Model\DepartmentUser;
use App\Model\DepartmentUserJob;
use App\Model\DepartmentTaskStatus;
use App\Model\DepartmentUserJobStatus;
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
    public function listProject(Request $request) {
        $name = $request->name;
        $manager_id = $request->manager;
        $db = Project::select('*');

        if ($this->isManager()) {
            $db->where('manager', $this->auth->id);
        } else if ($this->isUser()) {
            $department_user = DepartmentUser::where('user_id', $this->auth->id)->first();
            $project_ids = array();

            $department_task = DepartmentTask::where('department_id', $department_user->department_id);
            foreach ($department_task->get() as $_department_task) {
                $task = Task::find($_department_task->task_id);
                if ($task) {
                    $project_ids[] = $task->project_id;
                }
            }
            
            if (count($project_ids) > 0) {
                $db->whereIn('id', $project_ids);
            } else {
                $db->where('id', 0);
            }
        }

        if ($name) {
            $db->whereRaw('name LIKE "%' . $name . '%"');
        }

        if($manager_id > 0) {
            $db->where('manager', $manager_id);
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
        if (!$describe) {
            $describe = '';
        }

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

        if (!$describe) {
            $describe = '';
        }

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
        $tasks = Task::where('project_id', $id);
        foreach($tasks->get() as $_task) {
            $jobs = Job::where('task_id', $_task->id);
            foreach($jobs->get() as $_job) {
                $department_user_job = DepartmentUserJob::where('job_id', $_job->id);
                foreach($department_user_job->get() as $_department_user_job) {
                    DepartmentUserJobStatus::where('department_user_job_id', $_department_user_job->id)->delete();
                }
                $department_user_job->delete();
            }
            $jobs->delete();

            $department_task = DepartmentTask::where('task_id', $_task->id);
            foreach($department_task->get() as $_department_task) {
                DepartmentTaskStatus::where('department_task_id', $_department_task->id)->delete();
                $department = Department::find($_department_task->department_id);
                foreach($department->get() as $_department) {
                    $department_user = DepartmentUser::where('department_id', $_department->id);
                    foreach($department_user->get() as $_department_user) {
                        $department_user_job = DepartmentUserJob::where('department_user_id', $_department_user->id);
                        foreach($department_user_job->get() as $_department_user_job) {
                            DepartmentUserJobStatus::where('department_user_job_id', $_department_user_job->id)->delete();
                        }
                        $department_user_job->delete();
                    }   
                    $department_user->delete();
                }
            }
            $department_task->delete();
        }
        $tasks->delete();
        Project::find($id)->delete();

        return $this->success('Xóa dự án thành công');
    }

    /**
     * Lấy thông tin dự án bằng id
     */
    public function getProjectById(Request $request) {
        $project_id = $request->id;
        if (!$project_id) {
            return $this->success('Thông tin dự án', []);
        }

        $project = new ProjectResource(Project::find($project_id));
        return $this->success('Thông tin dự án', $project);
    }
}
