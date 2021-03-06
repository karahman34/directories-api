<?php

namespace App\Providers;

use App\Events\FileCreated;
use App\Events\UserRegistered;
use App\Listeners\CreateUserSetting;
use App\Listeners\CreateUserStorage;
use App\Listeners\IncreaseFolderSize;
use App\Listeners\IncreaseStorageSize;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        UserRegistered::class => [
            CreateUserStorage::class,
            CreateUserSetting::class,
        ],
        FileCreated::class => [
            IncreaseStorageSize::class,
            IncreaseFolderSize::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
