<?php

namespace App\Listeners;

use App\Events\FileCreated;
use App\Models\Folder;

class IncreaseFolderSize
{
    /**
     * Get folder model.
     *
     * @param   string  $folder_id
     *
     * @return  Folder
     */
    private function getFolder($folder_id)
    {
        return Folder::select('id', 'parent_folder_id', 'size')->where('id', $folder_id)->first();
    }

    /**
     * Increase folder size.
     *
     * @param   string  $folder_id
     * @param   float  $size
     *
     * @return  void
     */
    public function increaseFolderSize($folder_id, $size)
    {
        $folder = $this->getFolder($folder_id);

        if ($folder) {
            $folder->increment('size', $size);

            if (!is_null($folder->parent_folder_id)) {
                $this->increaseFolderSize($folder->parent_folder_id, $size);
            }
        }
    }

    /**
     * Handle the event.
     *
     * @param  FileCreated  $event
     * @return void
     */
    public function handle(FileCreated $event)
    {
        $this->increaseFolderSize($event->file->folder_id, $event->file->size);
    }
}
