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
use App\Model\ProjectStatus;
use App\Model\User;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;
use Mail;

use Illuminate\Support\Facades\Storage;
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
            $department_user = DepartmentUser::where('user_id', $this->auth->id)->latest('id')->first();
            $project_ids = array();
            if ($department_user) {
                $department_task = DepartmentTask::where('department_id', $department_user->department_id);
                if ($department_task) {
                    foreach ($department_task->get() as $_department_task) {
                        $task = Task::find($_department_task->task_id);
                        if ($task) {
                            $project_ids[] = $task->project_id;
                        }
                    }
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

        $list = $db->orderBy('id','desc')->paginate(5);
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
        $active = $request->active;
        $manager = $request->manager;
        $describe = $request->describe;
        $created_by = $request->created_by;

        $check_manager = User::find($manager);
        if (!$check_manager) return $this->error('Quản lý không tồn tại');

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
        }
        
        if ($this->checkExist('name', $name)) {
            return $this->error('Tên dự án đã tồn tại');
        }

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);

        $file = '';
        if ($request->file('file')) {
            $file = $request->file('file')->store('public/files');
            $file = str_replace('public/', '', $file);
        }

        $project_new = Project::create([
            'name' => $name,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'delay_time' => 0,
            'active' => $active,
            'manager' => $manager,
            'created_by' => $created_by,
            'file' => $file,
            'describe' => $describe,
        ]);

        if ($project_new) {
            ProjectStatus::create([
                'project_id' => $project_new->id,
                'content' => '',
                'status' => 0 // Đã giao
            ]);

            if ($check_manager->email) {
                $_name = $check_manager->fullname;
                if (!$_name) $_name = $check_manager->username;
                $content_mail = '<div>Xin chào ' . $_name . '!</div><div>Bạn đã được phân công quản lý dự án ' . $name . ', vui lòng kiểm tra. Cảm ơn!</div>';
                $this->_sendEmail($check_manager->email, $name, $content_mail);
            }
        }
        return $this->success('Thêm dự án thành công');
    }

    /**
     * Cập nhật dự án
     */
    public function updateProject(Request $request) {
        $active = $request->active;
        $describe = $request->describe;

        if (!$describe) {
            $describe = '';
        }

        $project = Project::find($request->id);
        if (!$project) 
            return $this->error('Dự án không tồn tại');

        if ($active != 1 && $active != 0) {
            return $this->error('Trạng thái là bắt buộc');
        }
        $file = $project->file;
        if ($request->file('file')) {
            if (!empty($file)) {
                Storage::disk('public')->delete($file);
            }

            $file = $request->file('file')->store('public/files');
            $file = str_replace('public/', '', $file);
        }
        

        Project::find($request->id)->update([
            'active' => $active,
            'file' => $file,
            'describe' => $describe,
        ]);

        return $this->success('Cập nhật dự án thành công');
    }

    /**
     * Xóa dự án
     */
    public function deleteProject(Request $request) {
        $id = $request->id;
        $project = Project::find($id);
        if (!$project) {
            return $this->error('Dự án không tồn tại');
        }
        $tasks = Task::where('project_id', $id);
        if ($tasks->count() > 0) 
            return $this->error('Dự án đã được phân công công việc. Không thể xóa');

        $project_status = ProjectStatus::where('project_id', $id)->delete();
        if ($project->file) {
            Storage::disk('public')->delete($project->file);
        }
        Project::find($id)->delete();

        return $this->success('Xóa dự án thành công');
    }

    /**
     * Lấy thông tin dự án bằng id
     */
    public function getProjectById(Request $request) {
        $project_id = $request->id;
        $project = Project::find($project_id);
    
        if (!$project) {
            return $this->error('Dự án không tồn tại');
        }

        /** Kiểm tra role manager & user có thuộc dự án? */
        if ($this->auth->role->level == 3) {
            if ($this->auth->id != $project->create_by && $this->auth->id != $project->manager)
                return $this->error('Bạn không thuộc dự án này');
        }

        if ($this->auth->role->level == 4) {
            $checkUserBelongProject = false; // Check user có thuộc project
            $tasks = Task::where('project_id', $project_id)->get();
            if ($tasks) {
                foreach ($tasks as $_task) {
                    $department_task = DepartmentTask::where('task_id', $_task->id)->latest('id')->first();
                    if ($department_task) {
                        $department_users = DepartmentUser::where('department_id', $department_task->department_id)->get();
                        if ($department_users) {
                            foreach ($department_users as $_department_user) {
                                if ($_department_user && $_department_user->user_id == $this->auth->id)
                                $checkUserBelongProject = true;
                            }
                        }
                    }
                }
            }

            if (!$checkUserBelongProject) 
                return $this->error('Bạn không thuộc dự án này');
        }

        $project = new ProjectResource(Project::find($project_id));
        return $this->success('Thông tin dự án', $project);
    }

    /** Xóa tệp đính kèm */
    public function deleteFile(Request $request) {
        $id = $request->id_task;
        $project = Project::find($id);
        if (!$project) return $this->error('Dự án không tồn tại');

        Storage::disk('public')->delete($project->file);

        $project->update([
            'file' => ''
        ]);
        return $this->success('Xóa tệp đính kèm thành công');
    }

    /**
     * Tiếp nhận dự án
     */
    public function takeProject(Request $request, $project_id) {
        $project = Project::find($project_id);
        if (!$project) return $this->error('Dự án không tồn tại');

        ProjectStatus::create([
            'status' => 1,
            'content' => '',
            'project_id' => $project_id
        ]);

        return $this->success('Tiếp nhận dự án thành công', []);
    }


    /**
     * Hoàn thành dự án
     */
    public function finishProject(Request $request, $project_id) {
        $project = Project::find($project_id);
        if (!$project) return $this->error('Dự án không tồn tại');

        $task = Task::where('project_id', $project_id)->get();
        if ($task) {
            foreach ($task as $_task) {
                $department_task = DepartmentTask::where('task_id', $_task->id)->latest('id')->first();
                if ($department_task) {
                    $department_task_status = DepartmentTaskStatus::where('department_task_id', $department_task->id)
                        ->latest('id')->first();
                    
                    if ($department_task_status && $department_task_status->status != 3) {
                        return $this->error('Dự án còn công việc chưa hoàn thành');
                    }
                }
            }
        }

        // Tính thời gian delay
        $time_now = strtotime(date("Y-m-d"));
        
        $delay_time = ($project->end_time - $time_now - 24 * 60 * 60);
        if ($delay_time < 0)
            $delay_time = -$delay_time / (24 * 3600);
        else 
            $delay_time = 0;

        $project->update([
            'delay_time' => $delay_time
        ]);

        ProjectStatus::create([
            'status' => 9, // Đã hoàn thành
            'content' => '',
            'project_id' => $project_id
        ]);

        return $this->success('Chọn hoàn thành dự án thành công', []);
    }
}
