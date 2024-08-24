<?php

namespace App\Game\Messages\Providers;

use App\Flare\Events\KingdomServerMessageEvent;
use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Game\Messages\Listeners\KingdomServerMessageListener;
use App\Game\Messages\Listeners\SkillLeveledUpServerMessageListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventsProvider extends ServiceProvider
{
    /**
     * @var array[]
     */
    protected $listen = [

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
     */
    public function boot(): void
    {
        parent::boot();
    }
}
