<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TrashDirectoriesCollection extends ResourceCollection
{
    public $folders;
    public $files;
    
    public function __construct($folders, $files)
    {
        $this->folders = $folders;
        $this->files = $files;
    }
    
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'folders' => $this->folders->map(function ($folder) {
                return new FolderResource($folder);
            }),
            'files' => new FilesCollection($this->files),
        ];
    }
}
