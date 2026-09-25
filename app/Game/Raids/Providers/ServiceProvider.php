<?php

namespace App\Game\Raids\Providers;

use App\Game\Raids\Console\Commands\ResetDailyRaidAttackLimits;
use App\Game\Raids\Contracts\RaidIdentityQuery;
use App\Game\Raids\Services\RaidEventService;
use App\Game\Raids\Services\RaidIdentityQueryService;
use App\Game\Raids\Services\RaidMapConflictService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(RaidEventService::class, function () {
            return new RaidEventService;
        });

        $this->app->bind(RaidMapConflictService::class, function () {
            return new RaidMapConflictService;
        });

        $this->app->bind(RaidIdentityQuery::class, function () {
            return new RaidIdentityQueryService;
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
