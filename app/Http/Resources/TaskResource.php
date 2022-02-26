<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\DepartmentTask;
use App\Model\DepartmentTaskStatus;
use App\Model\Department;
use App\Model\Label;
use App\Model\Task;
use App\Model\PreTask;
use App\Model\Job;
use App\Model\DepartmentUserJob;
use App\Model\DepartmentUserJobStatus;
use App\Http\Resources\DepartmentTaskStatusResource;
use App\Http\Resources\DepartmentResource;

use Illuminate\Support\Facades\Storage;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $department_task = DepartmentTask::where('task_id', $this->id)->latest('id')->first();
        $status = new DepartmentTaskStatusResource(
            DepartmentTaskStatus::where('department_task_id', $department_task->id)
                ->latest('id')->first()
            );
        $department = new DepartmentResource(Department::find($department_task->department_id));

        /** file đính kèm */
        $file = $this->file;
        if (!empty($file) && !preg_match('/^https?:/', $file)) {
            $file = Storage::url($this->file);
        }

        $label = Label::find($this->label_id);

        $pre_tasks = array();
        $pre_task = PreTask::where('task_id', $this->id)->get();
        foreach ($pre_task as $_pre_task) {
            $pre_tasks[] = Task::find($_pre_task->pre_task_id);
        }

        // Thống kê các nhiệm vụ
        $job_statistic = [
            'finish' => 0,
            'overdue' => 0,
            'total' => 0,
            'finish_percent' => 100
        ];
        
        $job_list = Job::where('task_id', $this->id);
        if ($job_list->count() > 0) {
            $job_statistic['total'] = $job_list->count();
            foreach ($job_list->get() as $_job) {
                $department_user_job = DepartmentUserJob::where('job_id', $_job->id)->latest('id')->first();
                if ($department_user_job) {
                    $department_user_job_status = DepartmentUserJobStatus::where('department_user_job_id', $department_user_job->id)->latest('id')->first();

                    if ($department_user_job_status && $department_user_job_status->status == 3) 
                        $job_statistic['finish']++;
                    else {
                        $time_now = date("Y-m-d");
                        $time_now = strtotime($time_now);
                        if($time_now - $_job->end_time > 0) {
                            $job_statistic['overdue']++;
                        }
                    }
                }
            }

            $job_statistic['finish_percent'] = ceil($job_statistic['finish'] / $job_statistic['total'] * 100);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'describe' => $this->describe,
            'result' => $this->result,
            'start_time' => date("Y-m-d", $this->start_time),
            'end_time' => date("Y-m-d", $this->end_time),
            'delay_time' => $this->delay_time,
            'label_id' => $this->label_id,
            'project_id' => $this->project_id,
            'pre_task_ids' => $pre_tasks,
            'file' => $file,
            'status' => $status,
            'department_id' => $this->department_id,
            'department' => $department,
            'label' => $label,
            'job_statistic' => $job_statistic,
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y'),
        ];
    }
}
