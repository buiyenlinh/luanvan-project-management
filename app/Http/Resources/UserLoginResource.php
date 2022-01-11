<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\RoleResource;

class UserLoginResource extends JsonResource
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
        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'username' => $this->username,
            'email' => $this->email,
            'active' => $this->active,
            'gender' => $this->gender,
            'birthday' => $this->birthday,
            'avatar' => $this->avatar,
            'role' => new RoleResource($this->role()->first()),
            'token' => $this->token,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
