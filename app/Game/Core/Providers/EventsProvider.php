<?php
namespace App\Game\Core\Providers;

use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Listeners\CharacterLevelUpListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterEvent;
use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Listeners\AttackTimeOutListener;
use App\Game\Core\Listeners\UpdateTopBarListener;
use App\Game\Core\Listeners\UpdateCharacterListener;
use App\Game\Core\Listeners\DropsCheckListener;
use App\Game\Core\Listeners\GoldRushCheckListener;
use App\Game\Core\Listeners\CraftedItemTimeOutListener;


class EventsProvider extends ServiceProvider {

    protected $listen = [

        // When the battle is over, set the attack timeout.
        AttackTimeOutEvent::class => [
            AttackTimeOutListener::class,
        ],

        // When the character levels up, update the top bar:
        UpdateTopBarEvent::class => [
            UpdateTopBarListener::class,
        ],

        // When you craft an item.
        CraftedItemTimeOutEvent::class => [
            CraftedItemTimeOutListener::class,
        ],

        // Update character stats if the character gains a level.
        UpdateCharacterEvent::class => [
            UpdateCharacterListener::class,
        ],

        // Assign them to the character.
        DropsCheckEvent::class => [
            DropsCheckListener::class,
        ],

        // When a battle is over, check if we got a gold rush.
        GoldRushCheckEvent::class => [
            GoldRushCheckListener::class,
        ],


        // When the character levels up:
        CharacterLevelUpEvent::class => [
            CharacterLevelUpListener::class,
        ]
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
