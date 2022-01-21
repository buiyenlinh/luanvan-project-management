<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\RoleResource;
use Illuminate\Support\Facades\Storage;

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
        $avatar = $this->avatar;
        if (!empty($avatar) && !preg_match('/^https?:/', $avatar)) {
            $avatar = Storage::url($this->avatar);
        }

        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'active' => $this->active,
            'gender' => $this->gender,
            'birthday' => date("Y-m-d", $this->birthday),
            'avatar' => $avatar,
            'role' => new RoleResource($this->role()->first()), // role () là function được khai báo trong Model User
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y')
        ];
    }
}
