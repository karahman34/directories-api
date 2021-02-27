<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WithSubFolderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'storage_id' => $this->storage_id,
            'parent_folder_id' => $this->parent_folder_id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'files' => $this->files->map(function ($file) {
                return new FileResource($file);
            }),
            'sub_folders' => $this->sub_folders->map(function ($sub_folder) {
                return new FolderResource($sub_folder);
            })
        ];
    }
}