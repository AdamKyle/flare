<?php
namespace App\Game\Battle\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Battle\Events\UpdateCharacterEvent;
use App\Game\Battle\Events\DropsCheckEvent;
use App\Game\Battle\Events\GoldRushCheckEvent;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Listeners\UpdateCharacterListener;
use App\Game\Battle\Listeners\DropsCheckListener;
use App\Game\Battle\Listeners\GoldRushCheckListener;
use App\Game\Battle\Listeners\AttackTimeOutListener;

class EventsProvider extends ServiceProvider {

    protected $listen = [

        // Update character stats if the character gains a level.
        UpdateCharacterEvent::class => [
            UpdateCharacterListener::class,
        ],

        // When battle is over check if their are drops.
        // Assign them to the character.
        DropsCheckEvent::class => [
            DropsCheckListener::class,
        ],

        // When a battle is over, check if we got a gold rush.
        GoldRushCheckEvent::class => [
            GoldRushCheckListener::class,
        ],

        // When the battle is over, set the attack time out.
        AttackTimeOutEvent::class => [
            AttackTimeOutListener::class,
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
