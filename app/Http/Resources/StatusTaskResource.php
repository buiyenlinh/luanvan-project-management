<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Model\DepartmentTask;
use App\Model\DepartmentUser;
use App\Model\User;
use App\Model\Department;
use App\Model\Project;
use App\Model\Task;

class StatusTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = [];
        $department = [];
        $department_task = DepartmentTask::find($this->department_task_id);
        if ($department_task) {

            // Tìm quản lý
            if ($this->status == 0) {
                $task = Task::find($department_task->task_id);
                if ($task) {
                    $project = Project::find($task->project_id);
                    if ($project) {
                        $user = [
                            'position' => 'Quản lý dự án',
                            'info' => User::find($project->manager)
                        ];
                    }
                }
            } else {
                $department_user_leader = DepartmentUser::where('department_id', $department_task->department_id)
                    ->where('leader', 1)
                    ->where('active_leader', 1)
                    ->latest('id')
                    ->first();
                
                if ($department_user_leader) {
                    $user = [
                        'position' => 'Trưởng phòng',
                        'info' => User::find($department_user_leader->user_id)
                    ];
                }
            }
            

            
            $department = Department::find($department_task->department_id);
        }

        return [
            'id' => $this->id,
            'department_task_id' => $this->department_task_id,
            'department' => $department,
            'content' => $this->content,
            'status' => $this->status,
            'user' => $user,
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y')
        ];
    }
}
