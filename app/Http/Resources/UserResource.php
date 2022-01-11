<?php

namespace App\Http\Resources\Resource;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'role' => new RoleResource($this->role()->first()), // role () là function được khai báo trong Model User
            // 'role' => new RoleResource(Role::find($this->role_id)), // Sử dụng này cũng được, nhưng phải khai báo thêm Model Role
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
