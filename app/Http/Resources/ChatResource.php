<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $str = array_map('intval', explode('_', $this->code));
        $receiver = ($str[0] == $this->sender) ? $str[1] : $str[0]; 

        return [
            'id' => $this->id,
            'sender' => $this->sender,
            'receiver' => $receiver,
            'type' => $this->type,
            'content' => $this->content,
            'seen' => $this->seen == 1,
            'timestamp' => $this->created_at->timestamp * 1000,
            'time' => $this->created_at->format('H:i, d-m-Y')
        ];
    }
}
