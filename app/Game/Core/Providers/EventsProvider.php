<?php

namespace App\Game\Core\Providers;

use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateCharacterEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Listeners\CharacterLevelUpListener;
use App\Game\Core\Listeners\CraftedItemTimeOutListener;
use App\Game\Core\Listeners\DropsCheckListener;
use App\Game\Core\Listeners\GoldRushCheckListener;
use App\Game\Core\Listeners\UpdateCharacterCurrenciesListener;
use App\Game\Core\Listeners\UpdateCharacterInventoryCountListener;
use App\Game\Core\Listeners\UpdateCharacterListener;
use App\Game\Core\Listeners\UpdateTopBarListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventsProvider extends ServiceProvider
{
    protected $listen = [

        // When the character levels up, update the top bar.
        UpdateTopBarEvent::class => [
            UpdateTopBarListener::class,
        ],

        // When the character currencies updates.
        UpdateCharacterCurrenciesEvent::class => [
            UpdateCharacterCurrenciesListener::class,
        ],

        // When the count of the inventory updates.
        UpdateCharacterInventoryCountEvent::class => [
            UpdateCharacterInventoryCountListener::class,
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
