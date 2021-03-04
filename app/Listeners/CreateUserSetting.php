<?php

namespace App\Listeners;

use App\Events\UserRegistered;

class CreateUserSetting
{
    /**
     * Handle the event.
     *
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        $event->user->setting()->create([
            'trash' => 'enable'
        ]);
    }
}
