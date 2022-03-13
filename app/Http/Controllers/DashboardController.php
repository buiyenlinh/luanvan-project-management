<?php

namespace App\Http\Controllers;

use App\Model\Project;
use App\Model\ProjectStatus;
use App\Model\Department;
use App\Model\Task;
use App\Model\DepartmentTask;
use App\Model\DepartmentTaskStatus;
use App\Model\User;
use App\Model\DepartmentUser;
use App\Model\Job;
use App\Model\JobStatus;
use App\Model\DepartmentUserJobStatus;
use App\Model\DepartmentUserJob;

use App\Http\Functions;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use Functions;

    public function getNumber(Request $request) {
        $projects = Project::all();

        $data_project = [
            'total' => 0,
            'finished' => 0,
            'working' => 0,
            'late' => 0,
            'future' => 0
        ];

        if ($projects->count() > 0) {
            $data_project['total'] = $projects->count();
            foreach ($projects as $_project) {
                $_project_status = ProjectStatus::where('project_id', $_project->id)->latest('id')->first();
                if ($_project_status) {
                    if ($_project_status->status == 9)
                        $data_project['finished']++;
                    else {
                        $time_now = strtotime(date("Y-m-d"));
                        $end_time = strtotime(date("Y-m-d", $_project->end_time));
                        $start_time = strtotime(date("Y-m-d", $_project->start_time));

                        if ($time_now - $end_time > 24 * 60 * 60)   // Do thời gian hết hạn là trước end_time 1 ngày
                            $data_project['late']++;
                        

                        if ($time_now >= $start_time && $time_now < $end_time - 24 * 60 * 60) 
                            $data_project['working']++;

                        if ($time_now <= $start_time)
                            $data_project['future']++;
                    }
                }
            }
        }

        $data_department = [
            'total' => 0,
            'user_total' => 0,
            'user_lock' => 0,
            'user_active' => 0
        ];
        $departments = Department::all();
        if ($departments->count() > 0) {
            $data_department['total'] = $departments->count();
            foreach ($departments as $_department) {
                $department_user = DepartmentUser::where('department_id', $_department->id);
                if ($department_user->count() > 0) {
                    foreach ($department_user->get() as $_department_user) {
                        $user = User::find($_department_user->user_id);
                        if ($user) {
                            $data_department['user_total']++;
                            if ($user->active) $data_department['user_active']++;
                            else $data_department['user_lock']++;
                        }
                    }
                }
            }
        }
        

        $data_task = [
            'total' => 0,
            'finished' => 0,
            'working' => 0,
            'late' => 0,
            'future' => 0
        ];

        $tasks = Task::all();
        if ($tasks->count() > 0) {
            $data_task['total'] = $tasks->count();
            foreach ($tasks as $_task) {
                $department_task = DepartmentTask::where('task_id', $_task->id)->latest('id')->first();
                if ($department_task) {
                    $department_task_status = DepartmentTaskStatus::where('department_task_id', $department_task->id)->latest('id')->first();

                    if ($department_task_status) {
                        if ($department_task_status->status == 3)
                            $data_task['finished']++;
                        else {
                            $time_now = strtotime(date("Y-m-d"));
                            $end_time = strtotime(date("Y-m-d", $_task->end_time));
                            $start_time = strtotime(date("Y-m-d", $_task->start_time));

                            if ($time_now - $end_time > 24 * 60 * 60)   // Do thời gian hết hạn là trước end_time 1 ngày
                                $data_task['late']++;
                            

                            if ($time_now >= $start_time && $time_now < $end_time - 24 * 60 * 60) 
                                $data_task['working']++;

                            if ($start_time >= $time_now)
                                $data_task['future']++;
                        }
                    }
                }
            }
        }

        $data_user = [
            'total' => 0,
            'lock' => 0,
            'active' => 0
        ];

        $users = User::all();
        if ($users->count() > 0) {
            $data_user['total'] = $users->count();
            foreach ($users as $_user) {
                if ($_user->active) $data_user['active']++;
                else $data_user['lock']++;
            }
        }

        $data = [
            'project' => $data_project,
            'department' => $data_department,
            'task' => $data_task,
            'user' => $data_user
        ];

        return $this->success('Thống kê số lượng', $data);
    }

    /**
     * Lấy tỉ lệ % nhiệm vụ
     */
    public function getPercentJob(Request $request) {
        $data_pie_job_chart = [
            'labels' => ['Trễ', 'Đang thực hiện', 'Hoàn thành', 'Hoàn thành trễ', 'Tương lai'],
            'data' => [0, 0, 0, 0, 0]
        ];

        $data_pie_task_chart = [
            'labels' => ['Trễ', 'Đang thực hiện', 'Hoàn thành', 'Hoàn thành trễ', 'Tương lai'],
            'data' => [0, 0, 0, 0, 0]
        ];

        $month_now = getdate()['mon'];
        $date_now = getdate()['mday'];

        $jobs = Job::whereMonth('created_at', $month_now);

        if ($jobs->count() > 0) {
            foreach ($jobs->get() as $_job) {
                $department_user_job = DepartmentUserJob::where('job_id', $_job->id)->latest('id')->first();
                if ($department_user_job) {
                    $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $department_user_job->id)->latest('id')->first();

                    if ($department_user_job_status) {
                        $end_time_job = strtotime(date("Y-m-d", $_job->end_time));
                        $start_time_job = strtotime(date("Y-m-d", $_job->start_time));
                        $time_now = strtotime(date("Y-m-d"));
                        if ($department_user_job_status->status == 3) { // Hoàn thành
                            
                            $created_at = strtotime($department_user_job_status->created_at->format("Y-m-d"));

                            if ($end_time_job - $created_at > 24 * 3600) { // Hoàn thành đúng hạn
                                $data_pie_job_chart['data'][2]++;
                            } else { // Hoàn thành trễ hạn
                                $data_pie_job_chart['data'][3]++;
                            }
                        } else {
                            if ($time_now - $end_time_job > 24 * 3600) {// Do hạn là trước end time 1 ngày
                                $data_pie_job_chart['data'][0]++;
                            }

                            if ($time_now >= $start_time_job && $time_now <= $end_time_job - 24 * 3600) {
                                $data_pie_job_chart['data'][1]++;
                            }

                            if ($time_now < $start_time_job) {
                                $data_pie_job_chart['data'][4]++;
                            }
                        }
                    }
                }
            }
        }

        $tasks = Task::whereMonth('created_at', $month_now);
        if ($tasks->count() > 0) {
            foreach ($tasks->get() as $_task) {
                $department_task = DepartmentTask::where('task_id', $_task->id)->latest('id')->first();
                if ($department_task) {
                    $department_task_status = DepartmentTaskStatus::where('department_task_id', $department_task->id)->latest('id')->first();
                    if ($department_task_status) {
                        $end_time_task = strtotime(date("Y-m-d", $_task->end_time));
                        $start_time_task = strtotime(date("Y-m-d", $_task->start_time));
                        $time_now = strtotime(date("Y-m-d"));
                        if ($department_task_status->status == 3) { // Hoàn thành
                            $created_at = strtotime($department_task_status->created_at->format("Y-m-d"));

                            if ($end_time_task - $created_at > 24 * 3600) { // Hoàn thành đúng hạn
                                $data_pie_task_chart['data'][2]++;
                            } else { // Hoàn thành trễ hạn
                                $data_pie_task_chart['data'][3]++;
                            }
                        } else {
                            if ($time_now - $end_time_task > 24 * 3600) {// Do hạn là trước end time 1 ngày
                                $data_pie_task_chart['data'][0]++;
                            }

                            if ($time_now >= $start_time_task && $time_now <= $end_time_task - 24 * 3600) {
                                $data_pie_task_chart['data'][1]++;
                            }

                            if ($time_now < $start_time_task) {
                                $data_pie_task_chart['data'][4]++;
                            }
                        }
                    }
                }
            }
        }

        $job_count = 0;
        $task_count = 0;
        $labels = array();
        $data_label = [
            [
                'label' => 'Công việc',
                'data' => array(),
                'backgroundColor' => '#17a2b8',
                'borderColor' => '#17a2b8',
            ],
            [
                'label' => 'Nhiệm vụ',
                'data' => array(),
                'backgroundColor' => 'rgb(255, 99, 132)',
                'borderColor' => 'rgb(255, 99, 132)',
            ]
        ];

        for($i = 1; $i <= $date_now; $i++) {
            $labels[] = $i . '/' . $month_now;
            $job_count += Job::whereMonth('created_at', $month_now)
                ->whereDay('created_at', $i)->count();
            
            $task_count += Task::whereMonth('created_at', $month_now)
                ->whereDay('created_at', $i)->count();
            
            $data_label[0]['data'][] = $task_count;
            $data_label[1]['data'][] = $job_count;
        }

        $data = [
            'data_pie_job_chart' => $data_pie_job_chart,
            'data_pie_task_chart' => $data_pie_task_chart,
            'data_line_chart' => [
                'labels' => $labels,
                'data_label' => $data_label
            ]
        ];

        return $this->success('Phần trăm nhiệm vụ', $data);
    }
}
