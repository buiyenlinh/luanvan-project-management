<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\User;
use App\Model\ProjectStatus;
use App\Model\Task;
use App\Model\DepartmentTask;
use App\Model\DepartmentTaskStatus;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        $manager = User::find($this->manager);
        $created_by = User::find($this->created_by);
        $status = ProjectStatus::where('project_id', $this->id)->latest('id')->first();

        /** file đính kèm */
        $file = $this->file;
        if (!empty($file) && !preg_match('/^https?:/', $file)) {
            $file = Storage::url($this->file);
        }

        $task_statistic = [
            'total' => 0,
            'finish' => 0,
            'overdue' => 0,
            'finish_percent' => 100,
        ];

        // Thống kê công việc
        $task_list = Task::where('project_id', $this->id);
        if ($task_list->count()) {
            $task_statistic['total'] = $task_list->count();
            foreach ($task_list->get() as $_task) {
                $department_task = DepartmentTask::where('task_id', $_task->id)->latest('id')->first();
                $department_task_status = DepartmentTaskStatus::where('department_task_id', $department_task->id)->latest('id')->first();
                if ($department_task_status && $department_task_status->status == 3) {
                    $task_statistic['finish']++;
                } else {
                    $time_now = date("Y-m-d");
                    $time_now = strtotime($time_now);
                    if($time_now - $_task->end_time > 0) { // Trễ hạn
                        $task_statistic['overdue']++;
                    }
                }
            }
            
            $task_statistic['finish_percent'] = ceil($task_statistic['finish'] / $task_statistic['total'] * 100);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'describe' => $this->describe,
            'active' => $this->active,
            'start_time' => date("Y-m-d", $this->start_time),
            'end_time' => date("Y-m-d", $this->end_time),
            'delay_time' => $this->delay_time,
            'manager' => [
                'id' => $manager->id,
                'fullname' => $manager->fullname,
                'username'=> $manager->username
            ],
            'created_by' => [
                'id' => $created_by->id,
                'fullname' => $created_by->fullname,
                'username' => $created_by->username
            ],
            'file' => $file,
            'status' => $status,
            'task_statistic' => $task_statistic,
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y')
        ];
    }
}
