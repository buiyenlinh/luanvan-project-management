<?php

namespace App\Http\Controllers;

use App\Model\Project;
use App\Model\PreTask;
use App\Model\Task;
use App\Model\User;
use App\Model\Job;
use App\Model\DepartmentUserJob;
use App\Model\DepartmentUserJobStatus;
use App\Model\Department;
use App\Model\DepartmentTask;
use App\Model\DepartmentUser;
use App\Model\PreJob;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;



use Illuminate\Http\Request;
use App\Http\Functions;

class ChartController extends Controller
{
    use Functions;

    /**
     * Lấy thông tin dự án hoặc công việc
     */
    public function getInfo(Request $request, $name, $id) {
        if ($name != 'du-an' && $name != 'cong-viec')
            return $this->error('Vui lòng thử lại');

        $data = '';
        if ($name == 'du-an') {
            $data = new ProjectResource(Project::find($id));
            if (!$data) return $this->error('Dự án không tồn tại');
        }

        if ($name == 'cong-viec') {
            $data = new TaskResource(Task::find($id));
            if (!$data) return $this->error('Công việc không tồn tại');
        }

        return $this->success('Thông tin', $data);
    }

    /**
     * Lấy dữ liệu dự án hiển thị gantt cho dự án
     */
    public function getDataProjectForChart(Request $request, $project_id) {
        $project = Project::find($project_id);
        if (!$project) return $this->error('Dự án không tồn tại');

        $data = [];
        $tasks = Task::where('project_id', $project_id);
        if ($tasks->count() > 0) {
            foreach ($tasks->get() as $_task) {
                $pre_tasks = PreTask::where('task_id', $_task->id);

                $department_task = DepartmentTask::where('task_id', $_task->id)->latest('id')->first();
                $department = null;
                if ($department_task)
                    $department = Department::find($department_task->department_id);

                $start_time = date("Y-m-d", $_task->start_time);
                $end_time = date("Y-m-d", $_task->end_time);

                // Tính % hoàn thành của mỗi task
                $task_statistic = [
                    'finish_percent' => 100,
                    'finish' => 0,
                    'total' => 0
                ];

                $jobs = Job::where('task_id', $_task->id);
                if ($jobs->count() > 0) {
                    foreach ($jobs->get() as $_job) {
                        $task_statistic['total']++;
                        $department_user_job = DepartmentUserJob::where('job_id', $_job->id)->latest('id')->first();
                        if ($department_user_job) {
                            $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $department_user_job->id)->latest('id')->first();

                            if ($department_user_job_status && $department_user_job_status->status == 3) {
                                $task_statistic['finish']++;
                            }
                        }
                    }
                    
                    $task_statistic['finish_percent'] = ceil($task_statistic['finish'] / $task_statistic['total'] * 100);
                }
                
                

                // Tiên quyết
                if ($pre_tasks->count() > 0 && $department) {
                    $pre_task_id = '';
                    foreach ($pre_tasks->get() as $_pre_task) {
                        if ($pre_task_id == '')
                            $pre_task_id .= ''.$_pre_task->pre_task_id;
                        else {
                            $pre_task_id .= ','.$_pre_task->pre_task_id;
                        }
                    }

                    $item = array(''.$_task->id.'', $_task->name, $department->name, $start_time, $end_time, null, $task_statistic['finish_percent'], $pre_task_id);
                        $data[] = $item;
                } else {
                    $item = array(''.$_task->id.'', $_task->name, $department->name, $start_time, $end_time, null, $task_statistic['finish_percent'], null);
                    $data[] = $item;
                }
            }
        }

        return $this->success('Dữ liệu dự án', $data);
    }

    /**
     * Lấy dữ liệu hiển thị gantt cho công việc
     */
    public function getDataTaskForGantt(Request $request, $task_id) {
        $task = Task::find($task_id);
        if (!$task) return $this->error('Công việc không tồn tại');

        $data = [];
        $jobs = Job::where('task_id', $task->id);
        if ($jobs->count() > 0) {
            foreach ($jobs->get() as $_job) {

                $start_time = date("Y-m-d", $_job->start_time);
                $end_time = date("Y-m-d", $_job->end_time);

                // % hoàn thành
                $finish_percent = 0;
                $department_user_job = DepartmentUserJob::where('job_id', $_job->id)->latest('id')->first();
                if ($department_user_job) {
                    $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $department_user_job->id)->latest('id')->first();

                    if ($department_user_job_status && $department_user_job_status->status == 3) {
                        $finish_percent = 100;
                    }
                }

                // Thành viên tiếp nhận job
                $user = '';
                $department_user = DepartmentUser::find($department_user_job->department_user_id);
                if ($department_user) {
                    $_user = User::find($department_user->user_id);
                    if ($_user) {
                        $user = $_user->fullname;
                        if ($user == '') $user = $_user->username;
                    }
                }

                // Job tiên quyết
                $pre_job = PreJob::where('job_id', $_job->id);
                if ($pre_job->count() > 0) {
                    foreach ($pre_job as $_pre_job) {
                        $item = array(''.$_job->id.'', $_job->name, $user, $start_time, $end_time, null, $finish_percent, ''.$_pre_job->pre_job_id.'');

                        $data[] = $item;
                    }
                } else {
                    $item = array(''.$_job->id.'', $_job->name, $user, $start_time, $end_time, null, $finish_percent, null);
                    $data[] = $item;
                }
            }
        }

        return $this->success('Dữ liệu công việc', $data);
    }
}
