<?php

namespace App\Game\Character\CharacterAttack\Providers;

use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Character\CharacterAttack\Listeners\UpdateCharacterAttackListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventsProvider extends ServiceProvider
{
    /**
     * @var array[]
     */
    protected $listen = [

        // When a characters inventory or anything else changes:
        UpdateCharacterAttackEvent::class => [
            UpdateCharacterAttackListener::class,
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
