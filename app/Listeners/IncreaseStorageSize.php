<?php

namespace App\Listeners;

use App\Events\FileCreated;

class IncreaseStorageSize
{
    /**
     * Handle the event.
     *
     * @param  FileCreated  $event
     * @return void
     */
    public function handle(FileCreated $event)
    {
        $event->storage->increment('used_space', $event->file->size);
    }
}
