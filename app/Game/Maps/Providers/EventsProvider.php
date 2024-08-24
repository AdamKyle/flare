<?php

namespace App\Game\Maps\Providers;

use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Listeners\MoveTimeOutListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventsProvider extends ServiceProvider
{
    protected $listen = [

        // When a character moves
        MoveTimeOutEvent::class => [
            MoveTimeOutListener::class,
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
