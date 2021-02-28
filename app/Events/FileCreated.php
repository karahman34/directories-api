<?php

namespace App\Events;

use App\Models\File;
use App\Models\Storage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $file;
    public $storage;

    /**
     * Create a new event instance.
     *
     * @param   File     $file
     * @param   Storage  $storage
     *
     * @return  void
     */
    public function __construct(File $file, Storage $storage)
    {
        $this->file = $file;
        $this->storage = $storage;
    }
}
