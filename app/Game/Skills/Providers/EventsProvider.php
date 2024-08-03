<?php

namespace App\Game\Skills\Providers;

use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Listeners\UpdateSkillListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventsProvider extends ServiceProvider
{
    /**
     * @var array[]
     */
    protected $listen = [
        // When a character trains a skill:
        UpdateSkillEvent::class => [
            UpdateSkillListener::class,
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
