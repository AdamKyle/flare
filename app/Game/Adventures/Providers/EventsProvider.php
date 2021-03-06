<?php
namespace App\Game\Adventures\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Adventures\Events\EmbarkOnAdventureEvent;
use App\Game\Adventures\Listeners\EmbarkOnAdventureListener;
use App\Game\Maps\Listeners\MoveTimeOutListener;

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
