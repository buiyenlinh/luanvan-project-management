<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\DepartmentTask;
use App\Model\DepartmentTaskStatus;
use App\Model\Department;
use App\Model\Label;
use App\Model\Task;
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
        $department_task = DepartmentTask::where('task_id', $this->id)->first();
        $status = new DepartmentTaskStatusResource(
            DepartmentTaskStatus::where('department_task_id', $department_task->id)
                ->latest('created_at')->first()
            );
        $department = new DepartmentResource(Department::find($department_task->department_id));

        /** file đính kèm */
        $file = $this->file;
        if (!empty($file) && !preg_match('/^https?:/', $file)) {
            $file = Storage::url($this->file);
        }

        $label = Label::find($this->label_id);

        $pre_task = Task::find($this->pre_task_id);
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
            'pre_task_id' => $this->pre_task_id,
            'pre_task' => $this->pre_task,
            'file' => $file,
            'status' => $status,
            'department_id' => $this->department_id,
            'department' => $department,
            'label' => $label,
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y'),
        ];
    }
}
