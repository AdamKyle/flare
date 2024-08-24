<?php

namespace App\Game\Events\Providers;

use App\Game\Events\Console\Commands\EndScheduledEvent;
use App\Game\Events\Console\Commands\ProcessScheduledEvents;
use App\Game\Events\Console\Commands\RestartGlobalEventGoal;
use App\Game\Events\Console\Commands\StartMonthlyPvpEvent;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Services\KingdomEventService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->commands([
            EndScheduledEvent::class,
            ProcessScheduledEvents::class,
            StartMonthlyPvpEvent::class,
            RestartGlobalEventGoal::class,
        ]);

        $this->app->bind(EventGoalsService::class, function () {
            return new EventGoalsService;
        });

        $this->app->bind(KingdomEventService::class, function () {
            return new KingdomEventService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
