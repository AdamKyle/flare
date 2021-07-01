<?php
namespace App\Game\Core\Providers;

use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Listeners\CharacterLevelUpListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Core\Events\BuyItemEvent;
use App\Game\Core\Events\SellItemEvent;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterEvent;
use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\UpdateSkillEvent;
use App\Game\Core\Events\CreateAdventureNotificationEvent;
use App\Game\Core\Listeners\CreateAdventureNotificationListener;
use App\Game\Core\Listeners\UpdateSkillListener;
use App\Game\Core\Listeners\AttackTimeOutListener;
use App\Game\Core\Listeners\UpdateTopBarListener;
use App\Game\Core\Listeners\UpdateCharacterListener;
use App\Game\Core\Listeners\DropsCheckListener;
use App\Game\Core\Listeners\GoldRushCheckListener;
use App\Game\Core\Listeners\CraftedItemTimeOutListener;
use App\Game\Core\Listeners\SellItemListener;
use App\Game\Core\Listeners\BuyItemListener;


class EventsProvider extends ServiceProvider {

    protected $listen = [

        // When the battle is over, set the attack time out.
        AttackTimeOutEvent::class => [
            AttackTimeOutListener::class,
        ],

        // When the character levels up, update the top bar:
        UpdateTopBarEvent::class => [
            UpdateTopBarListener::class,
        ],

        // When a character buys an item
        BuyItemEvent::class => [
            BuyItemListener::class,
        ],

        // When a character sells an item
        SellItemEvent::class => [
            SellItemListener::class,
        ],

        // When you craft an item.
        CraftedItemTimeOutEvent::class => [
            CraftedItemTimeOutListener::class,
        ],

        // Update character stats if the character gains a level.
        UpdateCharacterEvent::class => [
            UpdateCharacterListener::class,
        ],

        UpdateSkillEvent::class => [
            UpdateSkillListener::class,
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

        // When an adventure ends we create the notification:
        CreateAdventureNotificationEvent::class => [
            CreateAdventureNotificationListener::class,
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
