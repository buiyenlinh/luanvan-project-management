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
use App\Model\ProjectStatus;
use App\Model\DepartmentTaskStatus;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\JobResource;
use App\Http\Resources\StatusJobResource;
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

        /**
         * Check user login có thuộc công việc???
         */
        if ($this->auth->role->level == 3) {
            if ($this->auth->id != $project->create_by && $this->auth->id != $project->manager)
                return $this->error('Bạn không thuộc dự án này');
        }

        if ($this->auth->role->level == 4) { // trưởng phòng hoặc thành viên
            $checkUserBelongTask = false;
            $department_task = DepartmentTask::where('task_id', $task_id)->latest('id')->first();
            if ($department_task) {
                $department_users = DepartmentUser::where('department_id', $department_task->department_id)->get();
                if ($department_users) {
                    foreach ($department_users as $_department_user) {
                        if ($_department_user && $_department_user->user_id == $this->auth->id)
                            $checkUserBelongTask = true;
                    }
                }
            }
            if (!$checkUserBelongTask)
                return $this->error('Bạn không thuộc công việc này');
        }
        
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
                // if ($_department_user && $_department_user->leader == 0) // == 0 chỉ phân công cho thành viên
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
        $data = array();
        $jobs = array();
        if (!$name) {
            if ($request->callfirst)
                $jobs = Job::where('task_id', $task_id)->get();
            else
                return $this->success('Danh sách công việc tìm kiếm', []);
        } else
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

        $check_job = Job::where('name', $name)->where('task_id', $task_id)->count();
        if ($check_job > 0) return $this->error('Tên nhiệm vụ đã tồn tại trong công việc này');

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
        $department_user = DepartmentUser::where('user_id', $user_id)->latest('id')->first();
        if (!$department_user) return $this->error('Thành viên chưa có nhóm làm việc');

        $department_task = DepartmentTask::where('task_id', $task_id)->first();
        if (!$department_task) return $this->error('Vui lòng thử lại');

        if ($department_user->department_id != $department_task->department_id)
            return $this->error('Thành viên không thuộc nhóm làm việc được phân công');

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

        // Thông báo cho thành viên nhận task
        $this->_sendRealtime([
            'name' => 'job',
            'notification' => [
                'title' => 'Thêm nhiệm vụ',
                'message' => 'Bạn vừa được phân công nhiệm vụ ' . $name
            ]
        ], 'user' . $user->id);

        /** Gửi mail cho thành viên nhận task */
        
        $_name = $user->fullname;
        if (!$_name) $_name = $user->username;
        $content_mail = '<div>Xin chào ' . $_name . '!</div><div> Bạn đã được phân công nhiệm vụ <b>' . $name . '</b> thuộc công việc <b>' . $task->name . '</b> của dự án <b>' . $project->name . '</b>.<br>Vui lòng kiểm tra.<br>Cảm ơn!</div>';
        $this->_sendEmail($user->email, 'Phân công nhiệm vụ ' . $task->name, $content_mail);

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
        $department_user = DepartmentUser::where('user_id', $user_id)->latest('id')->first();
        if (!$department_user) return $this->error('Thành viên chưa có nhóm làm việc');

        $department_task = DepartmentTask::where('task_id', $task_id)->first();
        if (!$department_task) return $this->error('Vui lòng thử lại');

        if ($department_user->department_id != $department_task->department_id)
            return $this->error('Thành viên không thuộc nhóm làm việc được phân công');

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
                $department_user_new = DepartmentUser::where('user_id', $user_id)->latest('id')->first();
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

                    // Gửi email cho user mới nhận job
                    $_name = $user->fullname;
                    if (!$_name) $_name = $user->username;
                    $content_mail = '<div>Xin chào ' . $_name . '!</div><div> Bạn được phân công nhiệm vụ <b>' . $job->name . '</b> thuộc công việc <b>' . $task->name . '</b> của dự án <b>' . $project->name . '</b>';
                    if ($user->email)
                        $this->_sendEmail($user->email, 'Phân công công việc ', $content_mail);

                    // Gửi thông báo cho user mới nhận job
                    $this->_sendRealtime([
                        'name' => 'job',
                        'notification' => [
                            'title' => 'Cập nhật nhiệm vụ',
                            'message' => 'Nhiệm vụ ' . $job->name . ' vừa được cập nhật phân công cho bạn'
                        ]
                    ], 'user' . $user_id);
                }
            } else {

                if ($department_user_old && $department_user_old->user_id) {
                    // Thông báo cập nhật job cho user cũ
                    $this->_sendRealtime([
                        'name' => 'job',
                        'notification' => [
                            'title' => 'Cập nhật nhiệm vụ',
                            'message' => 'Nhiệm vụ ' . $job->name . ' vừa được cập nhật'
                        ]
                    ], 'user' . $department_user_old->user_id);
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
                $department_user = DepartmentUser::where('user_id', $this->auth->id)->latest('id')->first();
                $job_ids = array();
                if ($department_user) {
                    $department_user_job = DepartmentUserJob::where('department_user_id', $department_user->id)->get();
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
        PreJob::where('pre_job_id', $job_id)->delete();

        // Xóa status
        $department_user_job = DepartmentUserJob::where('job_id', $job_id);

        $check = array();
        if ($department_user_job->count() > 0) {
            foreach ($department_user_job->get() as $_department_user_job) {
                DepartmentUserJobStatus::where('department_user_job_id', $_department_user_job->id)->delete();
            }
        }

        // Thông báo đến user nhận job này
        $_department_user_job_latest = DepartmentUserJob::where('job_id', $job_id)->latest('id')->first();
        if ($_department_user_job_latest) {
            $department_user = DepartmentUser::find($_department_user_job_latest->department_user_id);

            if ($department_user && $department_user->user_id) {
                $this->_sendRealtime([
                    'name' => 'job',
                    'notification' => [
                        'title' => 'Xóa nhiệm vụ',
                        'message' => 'Nhiệm vụ ' . $job->name . ' đã bị xóa'
                    ]
                ], 'user' . $department_user->user_id);
            }
        }
        

        // Xóa department_user_job
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

                    /** Gửi email cho trưởng phòng -> xem xét từ chối */
                    $_department_task = DepartmentTask::where('task_id', $task->id)->first();
                    if ($_department_task) {
                        $department_user_leader = DepartmentUser::where('department_id', $_department_task->department_id)
                            ->where('leader', 1)->where('active_leader', 1)->latest('id')->first();
                        if ($department_user_leader) {
                            $leader = User::find($department_user_leader->user_id);

                            if ($leader && $leader->email) {
                                // Gửi thông báo cho trưởng phòng
                                $this->_sendRealtime([
                                    'name' => 'job',
                                    'notification' => [
                                        'title' => 'Yêu cầu từ chối nhận nhiệm vụ',
                                        'message' => 'Nhiệm vụ ' . $job->name . ' vừa gửi yêu cầu từ chối nhận nhiệm vụ'
                                    ]
                                ], 'user' . $leader->id);

                                // GỬi email
                                $_name = $leader->fullname;
                                if (!$_name) $_name = $leader->username;
                                $content_mail = '<div>Xin chào ' . $_name 
                                . '!</div><div> Nhiệm vụ <b>' . $job->name . '</b> thuộc công việc <b>' 
                                . $task->name . '</b> của dự án <b>' . $project->name . '</b> đã gửi yêu cầu từ chối nhận.<br>Vui lòng kiểm duyệt.<br>Cảm ơn!</div>';

                                $this->_sendEmail($leader->email, 'Yêu cầu từ chối nhận nhiệm vụ ' . $task->name, $content_mail);
                            }
                        }
                    }
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

                    /** Gửi email cho thành viên chịu trách nhiệm với job */
                    $_department_user = DepartmentUser::find($_department_user_job->department_user_id);
                    if ($_department_user) {
                        $_user = User::find($_department_user->user_id);

                        if ($_user && $_user->id) {
                            //  Thông báo
                            $this->_sendRealtime([
                                'name' => 'job',
                                'notification' => [
                                    'title' => 'Yêu cầu từ chối nhận nhiệm vụ',
                                    'message' => 'Nhiệm vụ ' . $job->name . ' không được duyệt từ chối nhận'
                                ]
                            ], 'user' . $_user->id);
                        }

                        if ($_user && $_user->email) {
                            // Email
                            $_name = $_user->fullname;
                            if (!$_name) $_name = $_user->username;

                            $content_mail = '<div>Xin chào ' . $_name 
                            . '!</div><div>Yêu cầu từ chối nhận nhiệm vụ <b>' . $job->name . '</b> thuộc công việc <b>' 
                            . $task->name . '</b> của dự án <b>' . $project->name . '</b> của bạn đã không được duyệt.<br>Vui lòng kiểm tra và thực hiện.<br>Cảm ơn!</div>';

                            $this->_sendEmail($_user->email, 'Không duyệt yêu cầu từ chối nhận nhiệm vụ ' . $task->name, $content_mail);
                        }
                    }
                }
            }
        }

        return $this->success('Từ chối nhận nhiệm vụ đã được gửi', []);
    }




    /**  TỚI ĐÂY */

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
        $time_now = strtotime(date("Y-m-d"));
        $delay_time = ($job->end_time - $time_now - 24 * 60 * 60);
        
        if ($delay_time < 0)
            $delay_time = - $delay_time / (24 * 3600);
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

                    /** Gửi email cho leader => Báo duyệt hoàn thành */
                    $_department_task = DepartmentTask::where('task_id', $task->id)->latest('id')->first();
                    if ($_department_task) {
                        $leader_department_user = DepartmentUser::where('department_id', $_department_task->department_id)
                            ->where('leader', 1)->where('active_leader', 1)->latest('id')->first();
                        
                        if ($leader_department_user) {
                            $_leader = User::find($leader_department_user->user_id);

                            if ($_leader && $_leader->id) {
                                //  Thông báo
                                $this->_sendRealtime([
                                    'name' => 'job',
                                    'notification' => [
                                        'title' => 'Yêu cầu kiểm duyệt hoàn thành nhiệm vụ',
                                        'message' => 'Nhiệm vụ ' . $job->name . ' gửi yêu cầu kiểm duyệt hoàn thành'
                                    ]
                                ], 'user' . $_leader->id);
                            }

                            if ($_leader && $_leader->email) {
                                $_name = $_leader->fullname;
                                if (!$_name) $_name = $_leader->username;

                                $content_mail = '<div>Xin chào ' . $_name 
                                . '!</div><div>Nhiệm vụ <b>' . $job->name . '</b> thuộc công việc <b>' 
                                . $task->name . '</b> của dự án <b>' . $project->name . '</b> đã gửi yêu cầu kiểm duyệt hoàn thành.<br> Vui lòng kiểm tra.<br> Cảm ơn!</div>';

                                $this->_sendEmail($_leader->email, 'Yêu cầu kiểm duyệt hoàn thành nhiệm vụ ' . $task->name, $content_mail);
                            }
                        }
                    }
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

                    /** Thông báo đến user - job đã được duyệt */
                    $_department_user = DepartmentUser::find($_department_user_job->department_user_id);
                    if ($_department_user) {
                        $_user = User::find($_department_user->user_id);

                        if ($_user && $_user->id) {
                            //  Thông báo
                            $this->_sendRealtime([
                                'name' => 'job',
                                'notification' => [
                                    'title' => 'Phản hồi kiểm duyệt hoàn thành nhiệm vụ',
                                    'message' => 'Nhiệm vụ ' . $job->name . ' đã được duyệt hoàn thành'
                                ]
                            ], 'user' . $_user->id);
                        }

                        if ($_user && $_user->email) {
                            $_name = $_user->fullname;
                            if (!$_name) $_name = $_user->username;

                            $content_mail = '<div>Xin chào ' . $_name 
                            . '!</div><div>Yêu cầu duyệt hoàn thành nhiệm vụ <b>' . $job->name . '</b> thuộc công việc <b>' 
                            . $task->name . '</b> của dự án <b>' . $project->name . '</b> của bạn đã được duyệt.<br>Chúc mừng bạn.</div>';

                            $this->_sendEmail($_user->email, 'Duyệt hoàn thành nhiệm vụ ' . $task->name, $content_mail);
                        }
                    }
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

                    /** Gửi email cho user báo không được duyệt hoàn thành */
                    $_department_user = DepartmentUser::find($_department_user_job->department_user_id);
                    if ($_department_user) {
                        $_user = User::find($_department_user->user_id);

                        if ($_user && $_user->id) {
                            $this->_sendRealtime([
                                'name' => 'job',
                                'notification' => [
                                    'title' => 'phản hồi kiểm duyệt hoàn thành nhiệm vụ',
                                    'message' => 'Nhiệm vụ ' . $job->name . ' không được duyệt hoàn thành'
                                ]
                            ], 'user' . $_user->id);
                        }

                        if ($_user && $_user->id) {
                            $_name = $_user->fullname;
                            if (!$_name) $_name = $_user->username;

                            $content_mail = '<div>Xin chào ' . $_name 
                            . '!</div><div>Yêu cầu duyệt hoàn thành nhiệm vụ <b>' . $job->name . '</b> thuộc công việc <b>' 
                            . $task->name . '</b> của dự án <b>' . $project->name 
                            . '</b> của bạn đã không được duyệt.'
                            . '<br>Vui lòng kiểm tra lý do và thực hiện lại.<br>Cảm ơn!</div>';

                            $this->_sendEmail($_user->email, 'Không duyệt hoàn thành nhiệm vụ ' . $task->name, $content_mail);
                        }
                    }
                }
            }
        }

        return $this->success('Từ chối duyệt thành công', []);
    }

    /**
     * Lấy số lượng nhiệm vụ trễ & hôm nay && đang thực hiện
     */

    public function getNumberJob(Request $request) {
        $data = [
            'count' =>  0,
            'project' => [
                'late' => 0,
                'today'=> 0,
                'working' => 0
            ],
            'task' => [
                'late' => 0,
                'today'=> 0,
                'working' => 0
            ],
            'job' => [
                'late' => 0,
                'today'=> 0,
                'working' => 0
            ]
        ];
        $time_now = strtotime(date("Y-m-d"));

        if ($this->auth->role->level == 3) { // manager
            $projects = Project::where('manager', $this->auth->id)->get();
            if ($projects) {
                foreach ($projects as $_project) {
                    $project_status = ProjectStatus::where('project_id', $_project->id)->latest('id')->first();
                    if ($_project && $_project->active == 1 && $project_status && $project_status->status != 9) {
                        if ($time_now == $_project->end_time - 24 * 3600) {
                            $data['project']['today']++;
                            $data['count']++;
                        } else if ($_project->end_time - 24 * 3600 < $time_now) {
                            $data['project']['late']++;
                            $data['count']++;
                        }
                        else if($_project->start_time <= $time_now && $time_now < $_project->end_time - 24 * 3600) {
                            $data['project']['working']++;
                            $data['count']++;
                        }

                        // Các task manager cần duyệt
                        $tasks = Task::where('project_id', $_project->id)->get();
                        if ($tasks) {
                            foreach ($tasks as $_task) {
                                $department_task = DepartmentTask::where('task_id', $_task->id)->latest('id')->first();
                                if ($department_task) {
                                    $department_task_status = DepartmentTaskStatus::where('department_task_id', $department_task->id)->latest('id')->first();

                                    if ($department_task_status->status == 2) {
                                        if ($time_now == $_task->end_time - 24 * 3600) {
                                            $data['task']['today']++;
                                            $data['count']++;
                                        } else if ($_task->end_time - 24 * 3600 < $time_now) {
                                            $data['task']['late']++;
                                            $data['count']++;
                                        }
                                        else if($_task->start_time <= $time_now && $time_now < $_task->end_time - 24 * 3600) {
                                            $data['task']['working']++;
                                            $data['count']++;
                                        }
                                    }
                                }
                            }
                        }
                        
                    }
                }
            }
        }

        else if ($this->auth->role->level == 4) {
            $department_user = DepartmentUser::where('user_id', $this->auth->id)->latest('id')->first();
            if ($department_user) {
                if ($department_user->leader == 1 && $department_user->active_leader == 1) { // trưởng phòng

                    /** Lấy task nhóm làm việc thực hiện */
                    $department_task = DepartmentTask::where('department_id', $department_user->department_id)->get();
                    if ($department_task) {

                        foreach ($department_task as $_dep_task) {
                            $task = Task::find($_dep_task->task_id);

                            $department_task_status = DepartmentTaskStatus::where('department_task_id', $_dep_task->id)->latest('id')->first();

                            if ($department_task_status && $department_task_status->status != 3) {
                                if ($time_now == $task->end_time - 24 * 3600) {
                                    $data['task']['today']++;
                                    $data['count']++;
                                } else if ($task->end_time - 24 * 3600 < $time_now) {
                                    $data['task']['late']++;
                                    $data['count']++;
                                }
                                else if($task->start_time <= $time_now && $time_now < $task->end_time - 24 * 3600) {
                                    $data['task']['working']++;
                                    $data['count']++;
                                }
                            }

                            /** Danh sách job cần duyệt */
                            $jobs = Job::where('task_id', $task->id)->get();
                            if ($jobs) {
                                foreach ($jobs as $_job) {
                                    $department_user_job = DepartmentUserJob::where('job_id', $_job->id)->latest('id')->first();
                                    if ($department_user_job) {
                                        $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $department_user_job->id)->latest('id')->first();

                                        // Kiểm tra job này phải trưởng phòng thực hiện không
                                        $department_user = DepartmentUser::find($department_user_job->department_user_id);

                                        if ($department_user_job_status) {
                                            if (($department_user && $department_user->user_id == $this->auth->id && $department_user_job_status->status != 3) || ($department_user_job_status->status == 2 || $department_user_job_status->status == 5))
                                            if ($time_now == $_job->end_time - 24 * 3600) {
                                                $data['job']['today']++;
                                                $data['count']++;
                                            } else if ($_job->end_time - 24 * 3600 < $time_now) {
                                                $data['job']['late']++;
                                                $data['count']++;
                                            }
                                            else if($_job->start_time <= $time_now && $time_now < $_job->end_time - 24 * 3600) {
                                                $data['job']['working']++;
                                                $data['count']++;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else { // thành viên
                    $department_user_job = DepartmentUserJob::where('department_user_id', $department_user->id);
                    if ($department_user_job->count() > 0) {
                        foreach ($department_user_job->get() as $dep_user_job) {
                            $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $dep_user_job->id)->latest('id')->first();

                            if ($department_user_job_status && $department_user_job_status->status != 7 && $department_user_job_status->status != 3) { // Do status == 7 là đã đc đổi thành viên, 3 hoàn thành
                                $_job = Job::find($dep_user_job->job_id);

                                if ($time_now == $_job->end_time - 24 * 3600) {
                                    $data['job']['today']++;
                                    $data['count']++;
                                } else if ($_job->end_time - 24 * 3600 < $time_now) {
                                    $data['job']['late']++;
                                    $data['count']++;
                                }
                                else if($_job->start_time <= $time_now && $time_now < $_job->end_time - 24 * 3600) {
                                    $data['job']['working']++;
                                    $data['count']++;
                                }
                            }
                        }
                    }
                }
            }
        }
        // manager
            // duyệt/từ chối task => hiển thị vào trong task
            // project của manager bắt đầu hôm nay, trễ, đang làm => hiển thị tên project

        // trưởng phòng
            // duyệt/từ chối job => hiển thị job
            // task today, late, đang thực hiện, 
            // Công việc trưởng phòng đang thực hiện
        // User
            // job bắt đầu today, late, working

        return $this->success('Số lượng nhiệm vụ', $data);
    }

    /**
     * Lấy danh sách cần làm trễ or hôm nay
     */
    public function getJobLateOrToda(Request $request, $status) {
        $list = array();
        $time_now = date("Y-m-d");
        $time_now = strtotime($time_now);
        if ($this->auth->role->level == 3) { // Quản lý dự án
            $project = Project::where('manager', $this->auth->id)->get();
            if ($project) {
                foreach ($project as $_project) {
                    $project_status = ProjectStatus::where('project_id', $_project->id)->latest('id')->first();
                    // Dự án
                    if (($time_now - $_project->end_time >= 0 && $status == 'late') || ($time_now - $_project->start_time == 0 && $status == 'today') || ($time_now > $_project->start_time && $time_now <= $_project->end_time - 24 * 60 * 60 && $status == 'working')) {
                        if ($project_status->status != 9 && $_project->active == 1) {
                            $list[] = new ProjectResource($_project);
                        }
                    }
                }
            }
        } else if ($this->auth->role->level == 4) {
            $department_user = DepartmentUser::where('user_id', $this->auth->id)->latest('id')->first();
            if ($department_user) {
                if ($department_user->leader == 0) { // user thành viên
                    $department_user_job = DepartmentUserJob::where('department_user_id', $department_user->id)->get();
                    if ($department_user_job) {
                        foreach ($department_user_job as $_department_user_job) {
                            $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $_department_user_job->id)->latest('id')->first();
                            if ($department_user_job_status && $department_user_job_status->status != 2 && $department_user_job_status->status != 3 && $department_user_job_status->status != 7) {

                                $job = Job::find($_department_user_job->job_id);
                                if (($job && $time_now - $job->end_time >= 0 && $status == 'late') || ($job && $time_now - $job->start_time == 0 && $status == 'today') || ($job && $time_now > $job->start_time && $time_now <= $job->end_time - 24 * 60 * 60 && $status == 'working')){

                                    $task = Task::find($job->task_id);
                                    $project = Project::find($task->project_id);
                                    $list[] = [
                                        'job' => new JobResource($job),
                                        'task' => $task,
                                        'project' => $project
                                    ];
                                }
                            }
                        }
                    }
                } else {
                    $department_task = DepartmentTask::where('department_id', $department_user->department_id)->get();
                    if ($department_task) {
                        foreach ($department_task as $_department_task) {
                            $department_task_status = DepartmentTaskStatus::where('department_task_id', $_department_task->id)->latest('id')->first();

                            if ($department_task_status && $department_task_status->status != 2 && $department_task_status->status != 3 && $department_task_status->status != 8) {
                                $task = Task::find($_department_task->task_id);
                                $project = Project::find($task->project_id);

                                // Kiểm tra task của phòng tới hạn chưa
                                $_department_task = DepartmentTask::where('task_id', $task->id)->latest('id')->first();
                                if($_department_task) {
                                    $department_task_status = DepartmentTaskStatus::where('department_task_id', $_department_task->id)->latest('id')->first();
                                    
                                    if ($department_task_status && $department_task_status->status != 3) {
                                        if (($time_now - $task->end_time >= 0 && $status == 'late') || ($status == 'today' && $time_now - $task->start_time == 0) || ($time_now > $task->start_time && $time_now <= $task->end_time - 24 * 60 * 60 && $status == 'working')) {
                                            $list[] = [
                                                'job' => '',
                                                'task' => new TaskResource($task),
                                                'project' => $project,
                                            ];
                                        }
                                    }
                                }


                                // Lấy danh sách job (nhiệm vụ user này làm, nv duyệt hoàn thành, duyệt từ chối)
                                $job = Job::where('task_id', $task->id)->get();
                                if ($job) {
                                    foreach ($job as $_job) {
                                        $department_user_job = DepartmentUserJob::where('job_id', $_job->id)->latest('id')->first();
                                        if ($department_user_job) {
                                            $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $department_user_job->id)->latest('id')->first();

                                            // Kiểm tra phải là công việc user này thực hiện ko
                                            $department_user = DepartmentUser::find($department_user_job->department_user_id);
                                            
                                            if ($department_user && $department_user->user_id == $this->auth->id) {
                                                if ($department_user_job_status && $department_user_job_status->status != 2 && $department_user_job_status->status != 3 && $department_user_job_status->status != 7) {

                                                    if (($_job && $time_now - $_job->end_time >= 0 && $status == 'late') || ($_job && $time_now - $_job->start_time == 0 && $status == 'today') || ($_job && $time_now > $_job->start_time && $time_now <= $_job->end_time - 24 * 60 * 60 && $status == 'working')){

                                                        $list[] = [
                                                            'job' => new JobResource($_job),
                                                            'task' => $task,
                                                            'project' => $project,
                                                        ];
                                                    }
                                                }
                                            } else {
                                                // Cần duyệt hoàn thành & từ chối nhận job
                                                if ($department_user_job_status && $department_user_job_status->status == 2 || $department_user_job_status->status == 5) {
                                                    if (($_job && $time_now - $_job->end_time >= 0 && $status == 'late') || ($_job && $time_now - $_job->start_time == 0 && $status == 'today') || ($_job && $time_now > $_job->start_time && $time_now <= $_job->end_time - 24 * 60 * 60 && $status == 'working')){ // trễ
                                                        $list[] = [
                                                            'job' => new JobResource($_job),
                                                            'task' => $task,
                                                            'project' => $project
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->success('Danh sách', $list);
    }


    public function getWork(Request $request, $type, $status) {
        $list = array();
        $time_now = strtotime(date("Y-m-d"));

        if ($this->auth->role->level == 3) { // manager
            $projects = Project::where('manager', $this->auth->id)->get();
            if ($projects) {
                foreach ($projects as $_project) {
                    $project_status = ProjectStatus::where('project_id', $_project->id)->latest('id')->first();
                    if ($_project && $_project->active == 1 && $project_status && $project_status->status != 9) {

                        if ($type == 'project' && (($status == 'late' && $_project->end_time - 24 * 3600 < $time_now) || ($status == 'today' && $time_now == $_project->end_time - 24 * 3600) || ($status == 'working' && $_project->start_time <= $time_now && $time_now < $_project->end_time - 24 * 3600))) {
                            $list[] = [
                                'project' => new ProjectResource($_project),
                                'task' => null
                            ];
                        } 

                        // Các task manager cần duyệt
                        if ($type == 'task') {
                            $tasks = Task::where('project_id', $_project->id)->get();
                            if ($tasks) {
                                foreach ($tasks as $_task) {
                                    $department_task = DepartmentTask::where('task_id', $_task->id)->latest('id')->first();
                                    if ($department_task) {
                                        $department_task_status = DepartmentTaskStatus::where('department_task_id', $department_task->id)->latest('id')->first();

                                        if ($department_task_status->status == 2 && (($status == 'late' && $_task->end_time - 24 * 3600 < $time_now) || ($status == 'today' && $time_now == $_task->end_time - 24 * 3600) || ($status == 'working' && $_task->start_time <= $time_now && $time_now < $_task->end_time - 24 * 3600))) {
                                            $list[] = [
                                                'project' => $_project,
                                                'task' => new TaskResource($_task)
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        else if ($this->auth->role->level == 4) {
            $department_user = DepartmentUser::where('user_id', $this->auth->id)->latest('id')->first();
            if ($department_user) {
                if ($department_user->leader == 1 && $department_user->active_leader == 1) { // trưởng phòng

                    /** Lấy task nhóm làm việc thực hiện */
                    $department_task = DepartmentTask::where('department_id', $department_user->department_id)->get();
                    if ($department_task) {

                        foreach ($department_task as $_dep_task) {
                            $task = Task::find($_dep_task->task_id);
                            $project = Project::find($task->project_id);

                            $department_task_status = DepartmentTaskStatus::where('department_task_id', $_dep_task->id)->latest('id')->first();

                            if ($department_task_status && $department_task_status->status != 3) {

                                if ($type == 'task' && (($status == 'late' && $task->end_time - 24 * 3600 < $time_now) || ($status == 'today' && $time_now == $task->end_time - 24 * 3600) || ($status == 'working' && $task->start_time <= $time_now && $time_now < $task->end_time - 24 * 3600))) {
                                    $list[] = [
                                        'project' => new ProjectResource($project),
                                        'task' => new TaskResource($task),
                                        'job' => null
                                    ];
                                }
                            }

                            /** Danh sách job cần duyệt */
                            $jobs = Job::where('task_id', $task->id)->get();
                            if ($jobs) {
                                foreach ($jobs as $_job) {
                                    $department_user_job = DepartmentUserJob::where('job_id', $_job->id)->latest('id')->first();
                                    if ($department_user_job) {
                                        $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $department_user_job->id)->latest('id')->first();

                                        // Kiểm tra job này phải trưởng phòng thực hiện không
                                        $department_user = DepartmentUser::find($department_user_job->department_user_id);

                                        if ($department_user_job_status) {
                                            if (($department_user && $department_user->user_id == $this->auth->id && $department_user_job_status->status != 3) || ($department_user_job_status->status == 2 || $department_user_job_status->status == 5))

                                            if ($type == 'job' && (($status == 'late' && $_job->end_time - 24 * 3600 < $time_now) || ($status == 'today' && $time_now == $_job->end_time - 24 * 3600) || ($status == 'working' && $_job->start_time <= $time_now && $time_now < $_job->end_time - 24 * 3600))) {
                                                $list[] = [
                                                    'project' => new ProjectResource($project),
                                                    'task' => new TaskResource($task),
                                                    'job' => new JobResource($_job)
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                else { // thành viên
                    $department_user_job = DepartmentUserJob::where('department_user_id', $department_user->id)->get();
                    if ($department_user_job) {
                        foreach ($department_user_job as $dep_user_job) {
                            $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $dep_user_job->id)->latest('id')->first();

                            if ($department_user_job_status && $department_user_job_status->status != 7 && $department_user_job_status->status != 3) { // Do status == 7 là đã đc đổi thành viên, 3 hoàn thành
                                $_job = Job::find($dep_user_job->job_id);
                                $task = Task::find($_job->task_id);
                                $project = Project::find($task->project_id);

                                if ($type == 'job' && (($status == 'late' && $_job->end_time - 24 * 3600 < $time_now) || ($status == 'today' && $time_now == $_job->end_time - 24 * 3600) || ($status == 'working' && $_job->start_time <= $time_now && $time_now < $_job->end_time - 24 * 3600))) {
                                    $list[] = [
                                        'project' => new ProjectResource($project),
                                        'task' => new TaskResource($task),
                                        'job' => new JobResource($_job)
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        

        return $this->success('Danh sách work', $list);
    }

    /** Lấy danh sách job */
    public function getJobName(Request $request) {
        $list = Job::where('name', 'LIKE', '%' . $request->keyword . '%')->select('name')->distinct()->get();

        return $this->success('Danh sách tên nhiệm vụ', $list);
    }

    /**
     * Lịch sử nhiệm vụ
     */
    public function history(Request $request, $id, $task_id, $job_id) {
        $project = Project::find($id);
        if (!$project)  return $this->error('Dự án không tồn tại');

        $task = Task::find($task_id);
        if (!$task)  return $this->error('Công việc không tồn tại');

        if ($project->id != $task->project_id)  return $this->error('Công việc không thuộc dự án');

        $job = Job::find($job_id);
        if (!$job)  return $this->error('Nhiệm vụ không tồn tại');
        
        if ($job->task_id != $task->id) return $this->error('Nhiệm vụ không thuộc công việc');
        
        $status = array();
        $department_user_job = DepartmentUserJob::where('job_id', $job_id);
        if ($department_user_job->count() > 0) {
            foreach ($department_user_job->get() as $_dep_user_job) {
                $list = DepartmentUserJobStatus::where('department_user_job_id', $_dep_user_job->id)->get();
                foreach ($list as $_list) {
                    $status[] = new StatusJobResource($_list);
                }
            }
        }

        $data = [
            'job' => new JobResource($job),
            'task' => $task,
            'project' => $project,
            'status' => $status
        ];

        return $this->success('Lịch sử nhiệm vụ', $data);
    }
}
