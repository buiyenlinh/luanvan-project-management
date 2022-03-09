<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\User;
use App\Model\DepartmentUser;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $created_by = User::find($this->created_by);
        $department_user = DepartmentUser::where('department_id', $this->id)
            ->where('leader', 0)->get();
        $members = array();
        foreach ($department_user as $_department_user) {
            $members[] = User::find($_department_user->user_id);
        }

        $department_leader = DepartmentUser::where('department_id', $this->id)
            ->where('leader', 1)->where('active_leader', 1)->latest('id')->first();
        $leader = User::find($department_leader->user_id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_by' => [
                'id' => $created_by->id,
                'fullname' => $created_by->fullname,
                'username' => $created_by->username
            ],
            'members' => $members,
            'leader' => $leader,
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y')
        ];
    }
}
