<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\PreJob;
use App\Model\User;
use App\Model\Job;
use App\Model\DepartmentUser;
use App\Http\Resources\DepartmentUserJobStatusResource;
use App\Http\Resources\UserResource;
use App\Model\DepartmentUserJob;
use App\Model\DepartmentUserJobStatus;
use Illuminate\Support\Facades\Storage;


class JobResource extends JsonResource
{
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $pre_jobs = PreJob::where('job_id', $this->id)->get();
        $pre_job_list = array();
        if ($pre_jobs) {
            foreach ($pre_jobs as $_pre_job) {
                $pre_job_list[] = Job::find($_pre_job->pre_job_id);
            }
        }

        /** file đính kèm */
        $file = $this->file;
        if (!empty($file) && !preg_match('/^https?:/', $file)) {
            $file = Storage::url($this->file);
        }

        $user = array();
        $status = array();

        $department_user_job = DepartmentUserJob::where('job_id', $this->id)->latest('id')->first();
        if ($department_user_job) {
            $department_user = DepartmentUser::find($department_user_job->department_user_id);
            $user = User::find($department_user->user_id);
            $status = new DepartmentUserJobStatusResource(
                DepartmentUserJobStatus::where('department_user_job_id', $department_user_job->id)->latest('id')->first()
            );   
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'content' => $this->content,
            'start_time' => date("Y-m-d", $this->start_time),
            'end_time' => date("Y-m-d", $this->end_time),
            'delay_time' => $this->delay_time,
            'task_id' => $this->task_id,
            'pre_job_ids' => $pre_job_list,
            'file' => $file,
            'status' => $status,
            'user' => $user,
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y'),
        ];
    }
}
