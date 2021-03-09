<?php

namespace App\Game\Kingdoms\Providers;

use App\Flare\Transformers\KingdomTransformer;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Service\BuildingService;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use App\Game\Kingdoms\Service\KIngdomsAttackService;
use App\Game\Kingdoms\Transformers\SelectedKingdom;
use League\Fractal\Manager;

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

        $this->app->bind(UnitService::class, function($app) {
            return new UnitService();
        });

        $this->app->bind(KingdomService::class, function($app) {
            return new KingdomService($app->make(KingdomBuilder::class));
        });

        $this->app->bind(KingdomResourcesService::class, function($app) {
            return new KingdomResourcesService($app->make(Manager::class), $app->make(KingdomTransformer::class));
        });

        $this->app->bind(SelectedKingdom::class, function() {
            return new SelectedKingdom();
        });

        $this->app->bind(KingdomsAttackService::class, function($app) {
            return new KingdomsAttackService($app->make(SelectedKingdom::class), $app->make(Manager::class));
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
