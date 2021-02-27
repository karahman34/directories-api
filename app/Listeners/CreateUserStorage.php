<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateUserStorage implements ShouldQueue
{
    /**
     * Create root folder.
     *
     * @param   Storage  $storage
     *
     * @return  void
     */
    private function createRootFolder(Storage $storage)
    {
        $storage->folders()->create([
            'name' => 'root'
        ]);
    }

    /**
     * Handle the event.
     *
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        $storage = $event->user->storage()->create([
            'space' => Storage::$defaultSpace,
            'used_space' => 0
        ]);

        $this->createRootFolder($storage);
    }
}
