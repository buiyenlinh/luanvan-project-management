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
use App\Model\DepartmentUserJobStatus;
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

        // Search bằng department_id
        if ($request->department_id) {
            $department_task = DepartmentTask::where('department_id', $request->department_id)->get();
            $task_ids = array();
            if ($department_task) {
                foreach ($department_task as $_department_task) {
                    $department_task_status_check = DepartmentTaskStatus::where('department_task_id', $_department_task->id)
                        ->latest('id')->first();
                    if ($department_task_status_check && $department_task_status_check->status != 8) {
                        $task_ids[] = $_department_task->task_id;
                    }
                }
                $db->whereIn('id', $task_ids);
            }
        }

        if ($this->isUser()) {
            $department_user = DepartmentUser::where('user_id', $this->auth->id)->first();
            if ($department_user) {
                $task_ids = array();
                if ($department_user->leader) {
                    $department_task = DepartmentTask::where('department_id', $department_user->department_id);
                    if ($department_task->count() > 0) {
                        foreach ($department_task->get() as $_department_task) {
                            $department_task_status_check = DepartmentTaskStatus::where('department_task_id', $_department_task->id)->latest('id')->first();

                            if ($department_task_status_check && $department_task_status_check->status != 8) {
                               $task_ids[] = $_department_task->task_id; 
                            }
                        }
                    }
                } else {
                    $department_user_job = DepartmentUserJob::where('department_user_id', $department_user->id);
                    if ($department_user_job->count() > 0) {
                        foreach ($department_user_job->get() as $_dep_user_job) {
                            $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $_dep_user_job->id)->latest('id')->first();

                            if ($department_user_job_status && $department_user_job_status->status != 8) {
                                $task_ids[] = Job::find($_dep_user_job->job_id)->task_id;
                            }
                        }
                    }
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
        

        $data = array();
        $tasks = Task::where('name', 'LIKE', '%' . $name . '%')->where('project_id', $project->id)->get();
        if ($tasks->count() > 0) {
            foreach($tasks as $_task) {
                $data[] = new TaskResource($_task);
            }
        }

        return $this->success('Danh sách công việc tìm kiếm', $data);
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
        if ($pre_task_ids) {
            foreach ($pre_task_ids as $_pre_task_id) {
                PreTask::create([
                    'task_id' => $new_task->id,
                    'pre_task_id' => $_pre_task_id
                ]);
            }
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
        $describe = $request->describe;
        $result = $request->result;
        $label_id = $request->label_id;
        $id = $request->id;

        $project = Project::find($project_id);
        if (!$project) {
            return $this->error('Dự án này không tồn tại');
        }

        $task = Task::find($id);
        if (!$task) return $this->error('Công việc cập nhật không tồn tại');

        if ($task->project_id != $project_id) return $this->error('Công việc không nằm trong dự án');

        if (!$label_id) {
            $label_id = 0;
        } else {
            $label_check = Label::find($label_id);
            if (!$label_check) return $this->error('Nhãn chọn không tồn tại');
        }

        if (!$describe) $describe = '';
        if (!$result) $result = '';

        if (!$label_id) {
            $label_id = 0;
        } else {
            if ($label_id < 0) return $this->error('Nhãn công việc không hợp lệ');
            else {
                $label_check = Label::find($label_id);
                if (!$label_check) return $this->error('Nhãn chọn không tồn tại');
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

        Task::find($id)->update([
            'describe' => $describe,
            'result' => $result,
            'label_id' => $label_id,
            'project_id' => $project_id,
            'file' => $file
        ]);


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


    /**
     * Check
     */
    public function checkError($project_id, $task_id) {
        $project = Project::find($project_id);
        if (!$project) return 'Dự án không tồn tại';

        $task = Task::find($task_id);
        if (!$task) return 'Công việc không tồn tại';

        if ($project_id != $task->project_id)
            return 'Công việc "' . $task->name . '" không nằm trong dự án "' . $project->name . '"'; 
    }

    /**
     * Tiếp nhận task
     */
    public function takeTask(Request $request, $project_id, $task_id) {
        $project = Project::find($project_id);
        $task = Task::find($task_id);

        $check_error = $this->checkError($project_id, $task_id);
        if ($check_error != '')
            return $this->error($check_error);
            

        $department_task = DepartmentTask::where('task_id', $task_id)->first();
        if ($department_task) {
            DepartmentTaskStatus::create([
                'content' => '',
                'status' => 1,
                'department_task_id' => $department_task->id
            ]);
        }

        return $this->success('Tiếp nhận công việc thành công', []);
    }


    /**
     * Hoàn thành task chờ duyệt
     */
    public function finishTask(Request $request, $project_id, $task_id) {
        $project = Project::find($project_id);
        $task = Task::find($task_id);

        $check_error = $this->checkError($project_id, $task_id);
        if ($check_error != '')
            return $this->error($check_error);

        $content = $request->content;
        if (!$content) $content = '';

        // Tính thời gian delay
        $time_now = date("Y-m-d");
        $time_now = strtotime($time_now);
        $delay_time = ($time_now - $task->end_time);
        if ($delay_time > 0)
            $delay_time = $delay_time / (24 * 3600);
        else 
            $delay_time = 0;
        
        $task->update([
            'delay_time' => $delay_time
        ]);

        // Check các job trong task có duyệt hoàn thành hết chưa?
        $jobs = Job::where('task_id', $task_id);
        if ($jobs->count() > 0) {
            foreach ($jobs->get() as $_job) {
                $department_user_job = DepartmentUserJob::where('job_id', $_job->id)->latest('id')->first();
                if ($department_user_job) {
                    $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $department_user_job->id)->latest('id')->first();
                    if ($department_user_job_status && $department_user_job_status->status != 3) {
                        return $this->error('Nhiệm vụ "' . $_job->name . '" của công việc này chưa được duyệt hoàn thành');
                    }
                }
            }
        }

        $department_task = DepartmentTask::where('task_id', $task_id)->first();
        if ($department_task) {
            DepartmentTaskStatus::create([
                'content' => $content,
                'status' => 2,
                'department_task_id' => $department_task->id
            ]);
        }

        return $this->success('Yêu cầu từ chối công việc đã được gửi thành công', []);
    }


    /**
     * Duyệt công việc
     */
    public function approvalFinishTask(Request $request, $project_id, $task_id) {
        $project = Project::find($project_id);
        $task = Task::find($task_id);

        $check_error = $this->checkError($project_id, $task_id);
        if ($check_error != '')
            return $this->error($check_error);
        
        $department_task = DepartmentTask::where('task_id', $task_id)->first();
        if ($department_task) {
            DepartmentTaskStatus::create([
                'content' => '',
                'status' => 3,
                'department_task_id' => $department_task->id
            ]);
        }

        return $this->success('Duyệt công việc thành công', []);
    }

    /**
     * Từ chối duyệt công việc
     */
    public function notApprovalFinishTask(Request $request, $project_id, $task_id) {
        $project = Project::find($project_id);
        $task = Task::find($task_id);

        $check_error = $this->checkError($project_id, $task_id);
        if ($check_error != '')
            return $this->error($check_error);
        
        $content = $request->content;
        if (!$content) return $this->error('Lý do từ chối duyệt là bắt buộc');

        $department_task = DepartmentTask::where('task_id', $task_id)->first();
        if ($department_task) {
            DepartmentTaskStatus::create([
                'content' => $content,
                'status' => 4,
                'department_task_id' => $department_task->id
            ]);
        }

        return $this->success('Từ chối duyệt công việc thành công', []);
    }
}
