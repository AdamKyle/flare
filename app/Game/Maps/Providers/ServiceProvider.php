<?php

namespace App\Game\Maps\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Flare\Cache\CoordinatesCache;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\PortService;
use App\Game\Maps\Values\MapPositionValue;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DistanceCalculation::class, function($app) {
            return new DistanceCalculation();
        });

        $this->app->bind(MapPositionValue::class, function($app) {
            return new MapPositionValue();
        });

        $this->app->bind(PortService::class, function($app) {
            return new PortService($app->make(DistanceCalculation::class), $app->make(MapPositionValue::class));
        });

        $this->app->bind(MapTileValue::class, function($app) {
            return new MapTileValue();
        });
        
        $this->app->bind(MovementService::class, function($app) {
            return new MovementService(
                $app->make(PortService::class),
                $app->make(MapTileValue::class),
                $app->make(CoordinatesCache::class),
                $app->make(MapPositionValue::class)
            );
        });

        $this->app->bind(LocationService::class, function($app) {
            return new LocationService(
                $app->make(PortService::class),
                $app->make(CoordinatesCache::class)
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
