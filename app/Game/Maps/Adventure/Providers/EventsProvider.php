<?php
namespace App\Game\Maps\Adventure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Game\Maps\Adventure\Events\EmbarkOnAdventureEvent;
use App\Game\Maps\Adventure\Listeners\EmbarkOnAdventureListener;
use App\Game\Maps\Adventure\Listeners\MoveTimeOutListener;

class EventsProvider extends ServiceProvider {

    protected $listen = [

        // When a character moves
        MoveTimeOutEvent::class => [
            MoveTimeOutListener::class,
        ],

        // When you embark on an adventure.
        EmbarkOnAdventureEvent::class => [
            EmbarkOnAdventureListener::class,
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
