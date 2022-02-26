<?php

namespace App\Http\Resources;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class LabelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** file đính kèm */
        $file = $this->file;
        if (!empty($file) && !preg_match('/^https?:/', $file)) {
            $file = Storage::url($this->file);
        }

        return [
            'id'=> $this->id,
            'name' => $this->name,
            'describe' => $this->describe,
            'file' => $file,
            'active' => $this->active,
            'created_at' => $this->created_at->format('H:i:s, d-m-Y'),
            'updated_at' => $this->updated_at->format('H:i:s, d-m-Y')
        ];
    }
}
