<?php

namespace App\Game\Character\CharacterCreation\Providers;

use App\Game\Character\CharacterCreation\Events\CreateCharacterEvent;
use App\Game\Character\CharacterCreation\Listeners\CreateCharacterListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventsProvider extends ServiceProvider
{
    /**
     * @var array[]
     */
    protected $listen = [

        // When a user registers for the first tme:
        CreateCharacterEvent::class => [
            CreateCharacterListener::class,
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
