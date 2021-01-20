<?php

namespace App\Game\Maps\Adventure\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Maps\Adventure\Services\PortService;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Values\MapPositionValue;
use App\Game\Maps\Adventure\Builders\RewardBuilder;
use App\Game\Maps\Adventure\Services\AdventureFightService;
use App\Game\Maps\Adventure\Services\AdventureService;
use App\Game\Core\Services\CharacterService;
use App\Game\Maps\Adventure\Services\MovementService;
use App\Game\Maps\Adventure\Values\MapTileValue;
use App\Game\Maps\Adventure\Values\WaterValue;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(PortService::class, function($app) {
            return new PortService($app->make(DistanceCalculation::class), $app->make(MapPositionValue::class));
        });

        $this->app->singleton(CharacterService::class, function($app) {
            return new CharacterService();
        });

        $this->app->bind(RewardBuilder::class, function($app) {
            return new RewardBuilder();
        });

        $this->app->bind(AdventureFightService::class, function($app, $parameters) {
            return new AdventureFightService($parameters['character'], $parameters['adventure']);
        });

        $this->app->bind(AdventureService::class, function($app, $parameters) {
            return new AdventureService(
                $parameters['character'], 
                $parameters['adventure'], 
                $parameters['rewardBuilder'],
                $parameters['name']
            );
        });

        $this->app->bind(MapTileValue::class, function($app) {
            return new MapTileValue();
        });
        
        $this->app->bind(MovementService::class, function($app) {
            return new MovementService($app->make(PortService::class));
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
