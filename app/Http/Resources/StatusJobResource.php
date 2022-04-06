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
                if ($this->status == 0 || $this->status == 3 || $this->status == 4 || $this->status == 6 || $this->status == 7) {
                    $leader = DepartmentUser::where('leader', 1)
                        ->where('department_id', $department_user->department_id)
                        ->where('active_leader', 1)
                        ->latest('id')
                        ->first();
                    
                    $user = [
                        'position' => 'Trưởng phòng',
                        'info' => User::find($leader->user_id)
                    ];
                } else {
                    $user = [
                        'position' => 'Thành viên',
                        'info' => User::find($department_user->user_id)
                    ];
                }
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
