<?php

namespace App\Flare\Providers;

use App\Flare\Listeners\UserLoggedInListener;
use App\Flare\Listeners\UserRegisteredListener;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Listeners\UpdateSkillListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventsProvider extends ServiceProvider
{
    protected $listen = [

        // When a character trains a skill:
        UpdateSkillEvent::class => [
            UpdateSkillListener::class,
        ],

        // When a user logs in.
        Login::class => [
            UserLoggedInListener::class,
        ],

        // When a user is registered.
        Registered::class => [
            UserRegisteredListener::class,
        ],
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
