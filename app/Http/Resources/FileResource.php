<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
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
            'folder_id' => $this->folder_id,
            'path' => route('file.download', ['id' => $this->id]),
            'name' => $this->name,
            'size' => $this->size,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'folder_trashed' => $this->folder_trashed,
            'is_public' => $this->is_public,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
