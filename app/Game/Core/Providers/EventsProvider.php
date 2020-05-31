<?php
namespace App\Game\Core\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Core\Events\BuyItemEvent;
use App\Game\Core\Events\SellItemEvent;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Listeners\CraftedItemTimeOutListener;
use App\Game\Core\Listeners\SellItemListener;
use App\Game\Core\Listeners\BuyItemListener;


class EventsProvider extends ServiceProvider {

    protected $listen = [

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
