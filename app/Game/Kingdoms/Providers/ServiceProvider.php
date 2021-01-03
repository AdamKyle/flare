<?php

namespace App\Game\Kingdoms\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Service\BuildingService;
use App\Game\Kingdoms\Service\KingdomService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(KingdomBuilder::class, function($app) {
            return new KingdomBuilder();
        });

        $this->app->bind(BuildingService::class, function($app) {
            return new BuildingService();
        });

        $this->app->bind(KingdomService::class, function($app) {
            return new KingdomService($app->make(KingdomBuilder::class));
        });
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
