<?php

namespace App\Game\Battle\Providers;

use App\Flare\Builders\BuildMythicItem;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\ServerFight\Pvp\PvpAttack;
use App\Flare\Services\BuildMonsterCacheService;
use App\Game\Battle\Console\Commands\ClearCelestials;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\BattleDrop;
use App\Game\Battle\Services\CelestialFightService;
use App\Game\Battle\Services\ConjureService;
use App\Game\Battle\Services\MonthlyPvpFightService;
use App\Game\Battle\Services\MonthlyPvpService;
use App\Game\Battle\Services\PvpService;
use App\Game\Battle\Services\RaidBattleService;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use App\Game\Core\Services\GoldRush;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Skills\Services\DisenchantService;
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
        $this->app->bind(ConjureService::class, function($app) {
            return new ConjureService(
                $app->make(NpcServerMessageBuilder::class),
            );
        });

        $this->app->bind(GoldRush::class, function($app) {
            return new GoldRush();
        });

        $this->app->bind(BattleDrop::class, function($app) {
            return new BattleDrop(
                $app->make(RandomItemDropBuilder::class),
                $app->make(DisenchantService::class)
            );
        });

        $this->app->bind(CelestialFightService::class, function($app) {
            return new CelestialFightService($app->make(BattleEventHandler::class), $app->make(CharacterCacheData::class), $app->make(MonsterPlayerFight::class));
        });

        $this->app->bind(PvpService::class, function($app) {
            return new PvpService(
                $app->make(PvpAttack::class),
                $app->make(BattleEventHandler::class),
                $app->make(MapTileValue::class),
                $app->make(BuildMythicItem::class)
            );
        });

        $this->app->bind(MonthlyPvpFightService::class, function($app) {
            return new MonthlyPvpFightService(
                $app->make(PvpService::class),
                $app->make(ConjureService::class),
                $app->make(BuildMythicItem::class)
            );
        });

        $this->app->bind(MonthlyPvpService::class, function($app) {
            return new MonthlyPvpService();
        });

        $this->app->bind(RaidBattleService::class, function($app) {
            return new RaidBattleService(
                $app->make(BuildMonster::class),
                $app->make(CharacterCacheData::class),
                $app->make(MonsterPlayerFight::class),
                $app->make(BuildMonsterCacheService::class),
                $app->make(BattleEventHandler::class)
            );
        });

        $this->app->bind(BattleEventHandler::class, function($app) {
            return new BattleEventHandler(
                $app->make(BattleRewardService::class),
                $app->make(SecondaryRewardService::class),
                $app->make(WeeklyBattleService::class),
            );
        });

        $this->commands([
            ClearCelestials::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        // ...
    }
}
