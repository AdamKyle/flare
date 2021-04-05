<?php

namespace App\Game\Kingdoms\Providers;

use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Service\UnitReturnService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Handlers\UnitHandler;
use App\Game\Kingdoms\Handlers\SiegeHandler;
use App\Game\Kingdoms\Service\AttackService;
use App\Game\Kingdoms\Service\KingdomBuildingService;
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

        $this->app->bind(KingdomBuildingService::class, function($app) {
            return new KingdomBuildingService;
        });

        $this->app->bind(UnitService::class, function($app) {
            return new UnitService;
        });

        $this->app->bind(KingdomService::class, function($app) {
            return new KingdomService($app->make(KingdomBuilder::class));
        });

        $this->app->bind(KingdomResourcesService::class, function($app) {
            return new KingdomResourcesService($app->make(Manager::class), $app->make(KingdomTransformer::class));
        });

        $this->app->bind(SelectedKingdom::class, function() {
            return new SelectedKingdom;
        });

        $this->app->bind(KingdomTransformer::class, function() {
            return new KingdomTransformer;
        });

        $this->app->bind(SiegeHandler::class, function() {
            return new SiegeHandler;
        });

        $this->app->bind(UnitHandler::class, function() {
            return new UnitHandler;
        });

        $this->app->bind(AttackService::class, function($app) {
            return new AttackService(
                $app->make(SiegeHandler::class),
                $app->make(UnitHandler::class),
                $app->make(KingdomResourcesService::class),
            );
        });

        $this->app->bind(UnitReturnService::class, function($app) {
           return new UnitReturnService();
        });

        $this->app->bind(KingdomsAttackService::class, function($app) {
            return new KingdomsAttackService(
                $app->make(SelectedKingdom::class),
                $app->make(Manager::class),
                $app->make(KingdomTransformer::class),
            );
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
