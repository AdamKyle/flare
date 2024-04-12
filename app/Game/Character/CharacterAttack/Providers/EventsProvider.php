<?php
namespace App\Game\Character\CharacterAttack\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Character\CharacterAttack\Listeners\UpdateCharacterAttackListener;

class EventsProvider extends ServiceProvider {

    /**
     * @var array[] $listen
     */
    protected $listen = [

        // When a characters inventory or anything else changes:
        UpdateCharacterAttackEvent::class => [
            UpdateCharacterAttackListener::class,
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
