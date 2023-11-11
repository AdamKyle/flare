<?php

namespace App\Game\Events\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Events\Console\Commands\EndScheduledEvent;
use App\Game\Events\Console\Commands\ProcessScheduledEvents;
use App\Game\Events\Console\Commands\StartMonthlyPvpEvent;
use App\Game\Events\Services\EventGoalsService;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {

        $this->commands([
            EndScheduledEvent::class,
            ProcessScheduledEvents::class,
            StartMonthlyPvpEvent::class,
        ]);

        $this->app->bind(EventGoalsService::class, function () {
            return new EventGoalsService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void {
    }
}
