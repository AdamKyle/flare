<?php

namespace App\Game\BattleRewardProcessing\Providers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Handlers\BattleEventHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\GlobalEventParticipationHandler;
use App\Game\BattleRewardProcessing\Handlers\PurgatorySmithHouseRewardHandler;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Core\Services\GoldRush;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Mercenaries\Services\MercenaryService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {

        $this->app->bind(FactionHandler::class, function($app) {
            return new FactionHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(GuideQuestService::class),
            );
        });

        $this->app->bind(GlobalEventParticipation::class, function($app) {
            return new GlobalEventParticipation(
                $app->make(RandomAffixGenerator::class),
            );
        });

        $this->app->bind(PurgatorySmithHouseRewardHandler::class, function($app) {
            return new PurgatorySmithHouseRewardHandler(
                $app->make(RandomAffixGenerator::class),
            );
        });

        $this->app->bind(BattleRewardService::class, function ($app) {
            return new BattleRewardService(
                $app->make(FactionHandler::class),
                $app->make(CharacterRewardService::class),
                $app->make(GoldRush::class),
                $app->make(GlobalEventParticipationHandler::class),
                $app->make(PurgatorySmithHouseRewardHandler::class)
            );
        });

        $this->app->bind(SecondaryRewardService::class, function ($app) {
            return new SecondaryRewardService(
                $app->make(MercenaryService::class),
                $app->make(ClassRankService::class),
            );
        });

        $this->app->bind(BattleEventHandler::class, function($app) {
            return new BattleEventHandler(
                $app->make(BattleRewardService::class),
                $app->make(SecondaryRewardService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
    }
}
