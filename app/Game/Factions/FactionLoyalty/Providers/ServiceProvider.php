<?php

namespace App\Game\Factions\FactionLoyalty\Providers;

use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
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

        $this->app->bind(FactionLoyaltyService::class, function ($app) {
            return new FactionLoyaltyService;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
