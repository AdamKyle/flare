<?php

namespace App\Game\Raids\Providers;

use App\Game\Raids\Console\Commands\ResetDailyRaidAttackLimits;
use App\Game\Raids\Services\RaidEventService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(RaidEventService::class, function () {
            return new RaidEventService;
        });

        $this->commands([
            ResetDailyRaidAttackLimits::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
