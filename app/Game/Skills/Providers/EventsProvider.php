<?php
namespace App\Game\Skills\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Listeners\UpdateSkillListener;

class EventsProvider extends ServiceProvider {

    /**
     * @var array[] $listen
     */
    protected $listen = [
        // When a character trains a skill:
        UpdateSkillEvent::class => [
            UpdateSkillListener::class,
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
