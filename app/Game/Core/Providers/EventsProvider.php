<?php
namespace App\Game\Core\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Core\Events\BuyItemEvent;
use App\Game\Core\Listeners\BuyItemListener;

class EventsProvider extends ServiceProvider {

    protected $listen = [

        // Update character stats if the character gains a level.
        BuyItemEvent::class => [
            BuyItemListener::class,
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
