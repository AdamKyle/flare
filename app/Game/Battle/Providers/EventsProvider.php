<?php
namespace App\Game\Battle\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\UpdateSkillEvent;
use App\Game\Battle\Listeners\UpdateSkillListener;
use App\Game\Battle\Listeners\AttackTimeOutListener;
use App\Game\Battle\Listeners\UpdateTopBarListener;

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

        UpdateSkillEvent::class => [
            UpdateSkillListener::class,
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
