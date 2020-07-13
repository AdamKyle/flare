<?php
namespace App\Game\Messages\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Game\Messages\Listeners\SkillLeveledUpServerMessageListener;
use App\Game\Messages\Listeners\ServerMessageListener;

class EventsProvider extends ServiceProvider {

    protected $listen = [

        // When a user registers for the first tme:
        ServerMessageEvent::class => [
            ServerMessageListener::class,
        ],

        // When a skill levels up.
        SkillLeveledUpServerMessageEvent::class => [
            SkillLeveledUpServerMessageListener::class,
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
