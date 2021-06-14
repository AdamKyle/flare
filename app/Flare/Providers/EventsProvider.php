<?php
namespace App\Flare\Providers;

use App\Flare\Listeners\UserLoggedInListener;
use App\Flare\Listeners\UserLoggedOutListener;
use App\Flare\Listeners\UserRegisteredListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Logout;
use App\Flare\Events\CreateCharacterEvent;
use App\Flare\Events\UpdateCharacterSheetEvent;
use App\Flare\Events\UpdateCharacterInventoryEvent;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Listeners\UpdateSkillListener;
use App\Flare\Listeners\UpdateCharacterAttackListener;
use App\Flare\Listeners\UpdateCharacterInventoryListener;
use App\Flare\Listeners\UpdateCharacterSheetListener;
use App\Flare\Listeners\CreateCharacterListener;

class EventsProvider extends ServiceProvider {

    protected $listen = [

        // When a user registers for the first tme:
        CreateCharacterEvent::class => [
            CreateCharacterListener::class,
        ],

        // When a character sheet should update.
        UpdateCharacterSheetEvent::class => [
            UpdateCharacterSheetListener::class,
        ],

        // When a characetr inventory updates:
        UpdateCharacterInventoryEvent::class => [
            UpdateCharacterInventoryListener::class,
        ],

        // When a characters inventory or anything else changes:
        UpdateCharacterAttackEvent::class => [
            UpdateCharacterAttackListener::class,
        ],

        // When a character trains a skill:
        UpdateSkillEvent::class => [
            UpdateSkillListener::class,
        ],

        // When a user logs in.
        Login::class => [
            UserLoggedInListener::class,
        ],

        // When a user is registered.
        Registered::class => [
            UserRegisteredListener::class,
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
