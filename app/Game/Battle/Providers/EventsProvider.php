<?php
namespace App\Game\Battle\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Battle\Events\UpdateCharacterEvent;
use App\Game\Battle\Listeners\UpdateCharacterListener;

class EventsProvider extends ServiceProvider {

    protected $listen = [

        // When a user registers for the first tme:
        UpdateCharacterEvent::class => [
            UpdateCharacterListener::class,
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
