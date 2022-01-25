<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Model\User;

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
            'alias' => $this->alias,
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y')
        ];
    }
}
