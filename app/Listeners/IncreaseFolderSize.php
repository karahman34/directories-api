<?php

namespace App\Listeners;

use App\Events\FileCreated;
use App\Jobs\IncreaseParentFolderSize;

class IncreaseFolderSize
{
    /**
     * Handle the event.
     *
     * @param  FileCreated  $event
     * @return void
     */
    public function handle(FileCreated $event)
    {
        // Increase parents folder size.
        IncreaseParentFolderSize::dispatchSync($event->file->folder_id, $event->file->size);
    }
}
