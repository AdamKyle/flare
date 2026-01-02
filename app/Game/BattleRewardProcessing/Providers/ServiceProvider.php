<?php

namespace App\Game\BattleRewardProcessing\Providers;

use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Handlers\BattleGlobalEventParticipationHandler;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use App\Game\BattleRewardProcessing\Handlers\GoldMinesRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\LocationSpecialtyHandler;
use App\Game\BattleRewardProcessing\Handlers\PurgatorySmithHouseRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\TheOldChurchRewardHandler;
use App\Game\BattleRewardProcessing\Services\BattleLocationRewardService;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Skills\Services\SkillService;
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

        $this->app->bind(FactionHandler::class, function ($app) {
            return new FactionHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(GuideQuestService::class),
                $app->make(BattleMessageHandler::class)
            );
        });

        $this->app->bind(FactionLoyaltyBountyHandler::class, function ($app) {
            return new FactionLoyaltyBountyHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(FactionLoyaltyService::class),
                $app->make(BattleMessageHandler::class),
            );
        });

        $this->app->bind(GlobalEventParticipation::class, function ($app) {
            return new GlobalEventParticipation(
                $app->make(RandomAffixGenerator::class),
            );
        });

        $this->app->bind(PurgatorySmithHouseRewardHandler::class, function ($app) {
            return new PurgatorySmithHouseRewardHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(BattleMessageHandler::class),
            );
        });

        $this->app->bind(GoldMinesRewardHandler::class, function ($app) {
            return new GoldMinesRewardHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(BattleMessageHandler::class),
            );
        });

        $this->app->bind(TheOldChurchRewardHandler::class, function ($app) {
            return new TheOldChurchRewardHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(BattleMessageHandler::class),
            );
        });

        $this->app->bind(LocationSpecialtyHandler::class, function ($app) {
            return new LocationSpecialtyHandler(
                $app->make(RandomAffixGenerator::class),
            );
        });

        $this->app->bind(WeeklyBattleService::class, function ($app) {
            return new WeeklyBattleService(
                $app->make(LocationSpecialtyHandler::class),
            );
        });

        $this->app->bind(BattleRewardService::class, function ($app) {
            return new BattleRewardService(
               $app->make(BattleMessageHandler::class),
                $app->make(CharacterRewardService::class),
                $app->make(FactionHandler::class),
                $app->make(FactionLoyaltyBountyHandler::class),
                $app->make(FactionLoyaltyService::class),
                $app->make(GoldRush::class),
                $app->make(BattleLocationRewardService::class),
                $app->make(DropCheckService::class),
                $app->make(WeeklyBattleService::class),
                $app->make(SecondaryRewardService::class),
                $app->make(BattleGlobalEventParticipationHandler::class),
                $app->make(SkillService::class),
            );
        });

        $this->app->bind(SecondaryRewardService::class, function ($app) {
            return new SecondaryRewardService(
                $app->make(ClassRankService::class),
            );
        });

        $this->app->bind(BattleMessageHandler::class, function () {
            return new BattleMessageHandler();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
