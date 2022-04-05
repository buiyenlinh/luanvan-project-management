<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Model\DepartmentUserJob;
use App\Model\DepartmentUser;
use App\Model\User;
class StatusJobResource extends JsonResource
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
        $department_user_job = DepartmentUserJob::find($this->department_user_job_id);
        if ($department_user_job) {
            $department_user = DepartmentUser::find($department_user_job->department_user_id);
            if ($department_user) {
                $user = User::find($department_user->user_id);
            }
        }
        return [
            'id' => $this->id,
            'department_user_job_id' => $this->department_user_job_id,
            'content' => $this->content,
            'status' => $this->status,
            'user' => $user,
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y')
        ];
    }
}
