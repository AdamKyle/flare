<?php

namespace App\Game\Maps\Providers;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Items\Transformers\QuestItemTransformer;
use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Battle\Services\ConjureService;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Console\Commands\UpdateMapCount;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\PctService;
use App\Game\Maps\Services\PortService;
use App\Game\Maps\Services\SetSailService;
use App\Game\Maps\Services\TeleportService;
use App\Game\Maps\Services\TraverseService;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Game\Maps\Services\WalkingService;
use App\Game\Maps\Transformers\LocationsTransformer;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Transformers\MonsterTransformer;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
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
        $this->app->bind(DistanceCalculation::class, function ($app) {
            return new DistanceCalculation;
        });

        $this->app->bind(PortService::class, function ($app) {
            return new PortService($app->make(DistanceCalculation::class));
        });

        $this->app->bind(TeleportService::class, function ($app) {
            return new TeleportService(
                $app->make(MapTileValue::class),
                $app->make(CoordinatesCache::class),
                $app->make(ConjureService::class),
                $app->make(MovementService::class),
                $app->make(TraverseService::class),
                $app->make(Manager::class),
                $app->make(CharacterSheetBaseInfoTransformer::class)
            );
        });

        $this->app->bind(WalkingService::class, function ($app) {
            return new WalkingService(
                $app->make(MapTileValue::class),
                $app->make(CoordinatesCache::class),
                $app->make(ConjureService::class),
                $app->make(MovementService::class),
                $app->make(TraverseService::class),
            );
        });

        $this->app->bind(UpdateRaidMonsters::class, function ($app) {
            return new UpdateRaidMonsters(
                $app->make(MapTileValue::class),
                $app->make(CoordinatesCache::class),
                $app->make(ConjureService::class),
                $app->make(MovementService::class),
                $app->make(TraverseService::class),
            );
        });

        $this->app->bind(SetSailService::class, function ($app) {
            return new SetSailService(
                $app->make(MapTileValue::class),
                $app->make(CoordinatesCache::class),
                $app->make(ConjureService::class),
                $app->make(MovementService::class),
                $app->make(PortService::class),
                $app->make(TraverseService::class),
            );
        });

        $this->app->bind(MapTileValue::class, function ($app) {
            return new MapTileValue;
        });

        $this->app->bind(PctService::class, function ($app) {
            return new PctService(
                $app->make(TraverseService::class),
                $app->make(MapTileValue::class),
            );
        });

        $this->app->bind(TraverseService::class, function ($app) {
            return new TraverseService(
                $app->make(Manager::class),
                $app->make(CharacterSheetBaseInfoTransformer::class),
                $app->make(BuildCharacterAttackTypes::class),
                $app->make(MonsterTransformer::class),
                $app->make(MonsterListService::class),
                $app->make(LocationService::class),
                $app->make(MapTileValue::class)
            );
        });

        $this->app->bind(LocationService::class, function ($app) {
            return new LocationService(
                $app->make(CoordinatesCache::class),
                $app->make(CharacterCacheData::class),
                $app->make(UpdateCharacterAttackTypesHandler::class),
                $app->make(QuestItemTransformer::class),
                $app->make(LocationsTransformer::class),
                $app->make(PlainDataSerializer::class),
                $app->make(Pagination::class),
                $app->make(Manager::class)
            );
        });

        $this->app->bind(MovementService::class, function ($app) {
            return new MovementService(
                $app->make(PortService::class),
                $app->make(MapTileValue::class),
                $app->make(CharacterSheetBaseInfoTransformer::class),
                $app->make(CoordinatesCache::class),
                $app->make(TraverseService::class),
                $app->make(ConjureService::class),
                $app->make(BuildMonsterCacheService::class),
                $app->make(LocationService::class),
                $app->make(Manager::class),
            );
        });

        $this->app->bind(LocationsTransformer::class, function () {
            return new LocationsTransformer;
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
