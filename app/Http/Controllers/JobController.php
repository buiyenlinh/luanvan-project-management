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
use App\Model\PreJob;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\JobResource;
use Illuminate\Support\Facades\Storage;


use App\Http\Functions;

class JobController extends Controller
{
    use Functions;

    /**
     * Check project & task & job
     */
    public function check($project_id, $task_id, $job_id = 0) {
        $str = '';
        $project = Project::find($project_id);
        $task = '';
        $job = '';
        if (!$project) 
            $str = 'Dự án này không tồn tại';

        if ($str == '') {
            $task = Task::find($task_id);
            if (!$task) 
                $str = 'Công việc không tồn tại';
        }
        
        if ($str == '' && $project_id != $task->project_id)
            $str = 'Công việc ' . $task->name . ' không thuộc dự án ' . $project->name;

        if ($job_id != 0 && $str == '') {
            $job = Job::find($job_id);
            if (!$job) $str = 'Nhiệm vụ không tồn tại';
            else {
              if ($job->task_id != $task_id) $str = 'Nhiệm vụ "' . $job->name . '" không tồn tại trong công việc "' . $task->name . '"';
            }
        }   

        return $str;
    }

    /**
     * Lấy thông tin dự án & công việc của nhiệm vụ
     */
    public function getInfo(Request $request, $id, $task_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);

        $check_error = $this->check($id, $task_id);
        if ($check_error != '')
            return $this->error($check_error);
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
        $task = Task::find($task_id);

        $check_error = $this->check($id, $task_id);
        if ($check_error != '')
            return $this->error($check_error);

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
        $task = Task::find($task_id);

        $check_error = $this->check($id, $task_id);
        if ($check_error != '')
            return $this->error($check_error);

        $name = $request->keyword;
        if (!$name) 
            return $this->success('Danh sách công việc tìm kiếm', []);

        $data = array();
        $jobs = Job::where('name', 'LIKE', '%' . $name . '%')->where('task_id', $task_id)->get();
        if ($jobs->count() > 0) {
            foreach($jobs as $_job) {
                $data[] = new JobResource($_job);
            }
        }
        return $this->success('Danh sách nhiệm vụ tìm kiếm', $data);
    }

    /**
     * Thêm nhiệm vụ
     */
    public function add(Request $request, $id, $task_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);

        $check_error = $this->check($id, $task_id);
        if ($check_error != '')
            return $this->error($check_error);

        $name = $request->name;
        $start_time = $request->start_time;
        $end_time = $request->end_time;
        $user_id = $request->user_id;
        $content = $request->content;
        $pre_job_ids = $request->pre_job_ids;

        if (!$name) return $this->error('Tên nhiệm vụ là bắt buộc');

        $check_job = Job::where('name', $name)->count();
        if ($check_job > 0) return $this->error('Tên nhiệm vụ đã tồn tại');

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

    /**
     * Cập nhật nhiệm vụ
     */
    public function update(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);
        $job = Job::find($job_id);

        $check_error = $this->check($id, $task_id, $job_id);
        if ($check_error != '')
            return $this->error($check_error);

        $user_id = $request->user_id;
        $content = $request->content;

        if (!$user_id) return $this->error('Người dùng là bắt buộc');
        if (!$content) $content = '';

        $user = User::find($user_id);
        if (!$user || !$user->active) return $this->error('Thành viên được phân công không tồn tại hoặc đã bị khóa');

        // Department User
        $department_user = DepartmentUser::where('user_id', $user_id)->first();
        if (!$department_user) return $this->error('Thành viên chưa có phòng ban');
        $department_task = DepartmentTask::where('task_id', $task_id)->first();
        if (!$department_task) return $this->error('Vui lòng thử lại');

        if ($department_user->department_id != $department_task->department_id)
            return $this->error('Thành viên không thuộc phòng ban được phân công');

        // File đính kèm
        $file = $job->file;
        if ($request->file('file')) {
            if (!empty($file)) {
                Storage::disk('public')->delete($file);
            }

            $file = $request->file('file')->store('public/files');
            $file = str_replace('public/', '', $file);
        }

        // Cập nhật nhiệm vụ
        Job::find($job_id)->update([
            'content' => $content,
            'file' => $file
        ]);

        // Kiểm tra user cập nhật có bị thay đổi
        $department_user_job = DepartmentUserJob::where('job_id', $job_id)->latest('id')->first();
        if ($department_user_job) {
            $department_user_old = DepartmentUser::find($department_user_job->department_user_id);
            if ($department_user_old && $department_user_old->user_id != $user_id) {
                // Cập nhật status cho user cũ
                DepartmentUserJobStatus::create([
                    'content' => '',
                    'status' => 7, // chuyển cho thành viên khác
                    'department_user_job_id' => $department_user_job->id
                ]);

                // Thêm department_user_job & status cho user mới
                $department_user_new = DepartmentUser::where('user_id', $user_id)->first();
                if ($department_user_new) {
                    $department_user_job_new = DepartmentUserJob::create([
                        'department_user_id' => $department_user_new->id,
                        'job_id' => $job_id
                    ]);

                    DepartmentUserJobStatus::create([
                        'content' => '',
                        'status' => 0,
                        'department_user_job_id' => $department_user_job_new->id
                    ]);
                }
            }
        }

        return $this->success("Cập nhật nhiệm vụ thành công", []);
    }


    /**
     * Danh sách
     */
    public function list(Request $request, $id, $task_id) {
        $project = Project::find($id);
        if (!$project) 
            return $this->error('Dự án này không tồn tại');
        
        $task = Task::find($task_id);
        if (!$task) 
            return $this->error('Công việc không tồn tại');

        if ($id != $task->project_id)
            return $this->error('Công việc ' . $task->name . ' không thuộc dự án ' . $project->name);

        $db = Job::select('*');
        $db->where('task_id', $task_id);

        if ($request->name) {
            $db->whereRaw('name LIKE "%' . $request->name . '%"');
        }

        if ($this->isUser()) {
            if (!$this->isLeader()) {
                $deparment_user = DepartmentUser::where('user_id', $this->auth->id)->first();
                $job_ids = array();
                if ($deparment_user) {
                    $department_user_job = DepartmentUserJob::where('department_user_id', $deparment_user->id)->get();
                    if ($department_user_job) {
                        foreach ($department_user_job as $_department_user_job) {
                            $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $_department_user_job->id)->latest('id')->first();
                            if ($department_user_job_status && $department_user_job_status->status != 7) {
                                $job_ids[] = $_department_user_job->job_id;
                            }
                        }
                    }
                    $db->whereIn('id', $job_ids);
                }
            }
        }
        $jobs = JobResource::collection($db->orderBy('id','desc')->get())->response()->getData();
        return $this->success('Danh sách nhiệm vụ tìm kiếm', $jobs);
    }


    /** Xóa file đính kèm */
    public function deleteFile(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);
        $job = Job::find($job_id);

        $check_error = $this->check($id, $task_id, $job_id);
        if ($check_error != '')
            return $this->error($check_error);

        if ($job->file)
            Storage::disk('public')->delete($job->file);

        $job->update([
            'file' => ''
        ]);
        return $this->success('Xóa tệp đính kèm thành công');
    }

    /**
     * Xóa nhiệm vụ
     */
    public function delete(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);
        $job = Job::find($job_id);

        $check_error = $this->check($id, $task_id, $job_id);
        if ($check_error != '')
            return $this->error($check_error);

        // Xóa prejob
        PreJob::where('job_id', $job_id)->delete();

        // Xóa status
        $department_user_job = DepartmentUserJob::where('job_id', $job_id);

        $check = array();
        if ($department_user_job->count() > 0) {
            foreach ($department_user_job->get() as $_department_user_job) {
                DepartmentUserJobStatus::where('department_user_job_id', $_department_user_job->id)->delete();
            }
        }
        // Xóa deparment_user_job
        $department_user_job->delete();

        if ($job->file) {
            Storage::disk('public')->delete($job->file);
        }
        // Xóa job
        $job->delete();

        return $this->success('Xóa nhiệm vụ thành công', $check);
    }


    /**
     * Tiếp nhận job
     */
    public function takeJob(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);
        $job = Job::find($job_id);

        $check_error = $this->check($id, $task_id, $job_id);
        if ($check_error != '')
            return $this->error($check_error);

        $department_user_job = DepartmentUserJob::where('job_id', $job_id)->get();
        if ($department_user_job) {
            foreach ($department_user_job as $_department_user_job) {
                if ($this->checkStatusJob($_department_user_job->id)) {
                    DepartmentUserJobStatus::create([
                        'content' => '',
                        'status' => 1,
                        'department_user_job_id' => $_department_user_job->id
                    ]);
                }
            }
        }

        return $this->success('Tiếp nhận thành công', []);
    }

    /**
     * Check status job có là chuyển cho ng khác ko
     */
    public function checkStatusJob($_department_user_job_id) {
        $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $_department_user_job_id)
            ->latest('id')->first();
        if ($department_user_job_status && $department_user_job_status->status != 7) 
            return true;
        return false;
    }

    /**
     * Từ chối nhận job
     */
    public function refuseJob(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);
        $job = Job::find($job_id);

        $check_error = $this->check($id, $task_id, $job_id);
        if ($check_error != '')
            return $this->error($check_error);

        $content = $request->content;
        
        if (!$content) return $this->error('Lý do từ chối nhiệm vụ là bắt buộc');

        $department_user_job = DepartmentUserJob::where('job_id', $job_id)->get();
        if ($department_user_job) {
            foreach ($department_user_job as $_department_user_job) {
                if ($this->checkStatusJob($_department_user_job->id)) {
                    DepartmentUserJobStatus::create([
                        'content' => $content,
                        'status' => 5,
                        'department_user_job_id' => $_department_user_job->id
                    ]);
                }
            }
        }

        return $this->success('Từ chối nhận nhiệm vụ đã được gửi', []);
    }


    /**
     * không duyệt yêu cầu từ chối nhận job
     */
    public function notApprovalRefuseJob(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);
        $job = Job::find($job_id);

        $check_error = $this->check($id, $task_id, $job_id);
        if ($check_error != '')
            return $this->error($check_error);

        $content = $request->content;
        
        if (!$content) return $this->error('Lý do không duyệt yêu cầu từ chối nhiệm vụ là bắt buộc');

        $department_user_job = DepartmentUserJob::where('job_id', $job_id)->get();
        if ($department_user_job) {
            foreach ($department_user_job as $_department_user_job) {
                if ($this->checkStatusJob($_department_user_job->id)) {
                    DepartmentUserJobStatus::create([
                        'content' => $content,
                        'status' => 6,
                        'department_user_job_id' => $_department_user_job->id
                    ]);
                }
            }
        }

        return $this->success('Từ chối nhận nhiệm vụ đã được gửi', []);
    }



    /**
     * Hoàn thành job
     */
    public function finishJob(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);
        $job = Job::find($job_id);

        $check_error = $this->check($id, $task_id, $job_id);
        if ($check_error != '')
            return $this->error($check_error);

        $content = $request->content;
        if (!$content) $content = '';


        // Tính thời gian delay
        $time_now = date("Y-m-d");
        $time_now = strtotime($time_now);
        $delay_time = ($time_now - $job->end_time);
        if ($delay_time > 0)
            $delay_time = $delay_time / (24 * 3600);
        else 
            $delay_time = 0;
        
        $job->update([
            'delay_time' => $delay_time
        ]);

        $department_user_job = DepartmentUserJob::where('job_id', $job_id)->get();
        if ($department_user_job) {
            foreach ($department_user_job as $_department_user_job) {
                if ($this->checkStatusJob($_department_user_job->id)) {
                    DepartmentUserJobStatus::create([
                        'content' => $content,
                        'status' => 2,
                        'department_user_job_id' => $_department_user_job->id
                    ]);
                }
            }
        }

        return $this->success('Yêu cầu duyệt hoàn thành nhiệm vụ đã được gửi', []);
    }


    /**
     * duyệt job
     */
    public function approvalJob(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);
        $job = Job::find($job_id);

        $check_error = $this->check($id, $task_id, $job_id);
        if ($check_error != '')
            return $this->error($check_error);

        $department_user_job = DepartmentUserJob::where('job_id', $job_id)->get();
        if ($department_user_job) {
            foreach ($department_user_job as $_department_user_job) {
                if ($this->checkStatusJob($_department_user_job->id)) {
                    DepartmentUserJobStatus::create([
                        'content' => '',
                        'status' => 3,
                        'department_user_job_id' => $_department_user_job->id
                    ]);
                }
            }
        }

        return $this->success('Duyệt thành công', []);
    }

    /**
     * không duyệt job
     */
    public function notApprovalJob(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        $task = Task::find($task_id);
        $job = Job::find($job_id);

        $check_error = $this->check($id, $task_id, $job_id);
        if ($check_error != '')
            return $this->error($check_error);

        $content = $request->content;
        if (!$content) return $this->error('Lý do từ chối duyệt là bắt buộc');

        $department_user_job = DepartmentUserJob::where('job_id', $job_id)->get();
        if ($department_user_job) {
            foreach ($department_user_job as $_department_user_job) {
                if ($this->checkStatusJob($_department_user_job->id)) {
                    DepartmentUserJobStatus::create([
                        'content' => $content,
                        'status' => 4,
                        'department_user_job_id' => $_department_user_job->id
                    ]);
                }
            }
        }

        return $this->success('Từ chối duyệt thành công', []);
    }
}
