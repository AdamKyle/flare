<?php

namespace App\Game\Events\Providers;

use App\Game\Events\Console\Commands\EndScheduledEvent;
use App\Game\Events\Console\Commands\ProcessScheduledEvents;
use App\Game\Events\Console\Commands\RestartGlobalEventGoal;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Services\KingdomEventService;
use App\Game\Events\Services\ScheduledEventDispatchService;
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
            RestartGlobalEventGoal::class,
        ]);

        $this->app->bind(EventGoalsService::class, function ($app) {
            return new EventGoalsService($app->make(GlobalEventGoalEligibilityService::class));
        });

        $this->app->bind(KingdomEventService::class, function () {
            return new KingdomEventService;
        });

        $this->app->bind(ScheduledEventDispatchService::class, function () {
            return new ScheduledEventDispatchService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
