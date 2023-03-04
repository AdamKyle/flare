<?php
namespace App\Game\Messages\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Flare\Events\KingdomServerMessageEvent;
use App\Game\Messages\Listeners\KingdomServerMessageListener;
use App\Game\Messages\Listeners\SkillLeveledUpServerMessageListener;
use App\Game\Messages\Listeners\ServerMessageListener;

class EventsProvider extends ServiceProvider {

    protected $listen = [

        // When a server message should be sent to the player.
        ServerMessageEvent::class => [
            ServerMessageListener::class,
        ],

        // When a skill levels up.
        SkillLeveledUpServerMessageEvent::class => [
            SkillLeveledUpServerMessageListener::class,
        ],

        // When a kingdom should send a server message to the player.
        KingdomServerMessageEvent::class => [
            KingdomServerMessageListener::class,
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
