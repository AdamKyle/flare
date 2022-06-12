<?php

namespace App\Game\Maps\Providers;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Game\Maps\Services\PctService;
use League\Fractal\Manager;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\MonsterTransformer;
use App\Game\Battle\Services\ConjureService;
use App\Game\Maps\Console\Commands\UpdateMapCount;
use App\Game\Maps\Services\TraverseService;
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

        $this->app->bind(PctService::class, function($app) {
            return new PctService(
                $app->make(TraverseService::class),
                $app->make(MapTileValue::class),
            );
        });

        $this->app->bind(TraverseService::class, function($app) {
           return new TraverseService(
               $app->make(Manager::class),
               $app->make(CharacterSheetBaseInfoTransformer::class),
               $app->make(BuildCharacterAttackTypes::class),
               $app->make(MonsterTransformer::class),
               $app->make(LocationService::class),
               $app->make(MapTileValue::class)
           );
        });

        $this->app->bind(LocationService::class, function($app) {
            return new LocationService(
                $app->make(CoordinatesCache::class),
                $app->make(CharacterCacheData::class),
            );
        });

        $this->app->bind(MovementService::class, function($app) {
            return new MovementService(
                $app->make(PortService::class),
                $app->make(MapTileValue::class),
                $app->make(CharacterSheetBaseInfoTransformer::class),
                $app->make(CoordinatesCache::class),
                $app->make(MapPositionValue::class),
                $app->make(TraverseService::class),
                $app->make(ConjureService::class),
                $app->make(BuildMonsterCacheService::class),
                $app->make(LocationService::class),
                $app->make(Manager::class),
            );
        });

        $this->commands([
            UpdateMapCount::class,
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
