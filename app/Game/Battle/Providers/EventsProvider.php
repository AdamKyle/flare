<?php
namespace App\Game\Battle\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Listeners\AttackTimeOutListener;


class EventsProvider extends ServiceProvider {

    protected $listen = [

        // When the battle is over, set the attack timeout.
        AttackTimeOutEvent::class => [
            AttackTimeOutListener::class,
        ],
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void {
        parent::boot();
    }
}
