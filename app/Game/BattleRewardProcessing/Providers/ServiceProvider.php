<?php

namespace App\Game\BattleRewardProcessing\Providers;

use App\Game\BattleRewardProcessing\Handlers\BattleGlobalEventParticipationHandler;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use App\Game\BattleRewardProcessing\Handlers\GoldMinesRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\LocationSpecialtyHandler;
use App\Game\BattleRewardProcessing\Handlers\PurgatorySmithHouseRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\TheOldChurchRewardHandler;
use App\Game\BattleRewardProcessing\Services\BattleLocationRewardService;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageContext;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\BattleRewardProcessing\Services\CharacterCurrencyRewardService;
use App\Game\BattleRewardProcessing\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Services\CharacterXPService;
use App\Game\BattleRewardProcessing\Services\FactionLoyaltyRewardRequestService;
use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Builders\BuildCosmicItem;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Core\Items\Builders\BuildUniqueItem;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Services\CharacterService;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Services\GlobalEventGoalProgressionService;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemWorldRewardService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Skills\Services\SkillService;
use App\Game\Tops\Services\BroadcastTopsUpdateService;
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
        $this->app->singleton(CharacterCurrencyRewardService::class, fn ($app) => new CharacterCurrencyRewardService(
            $app->make(BattleMessageHandler::class),
            $app->make(RandomNumberGenerator::class),
            $app->make(CharacterAreaGemEffectService::class),
        ));
        $this->app->bind(CharacterXPService::class, fn ($app) => new CharacterXPService(
            $app->make(CharacterService::class),
            $app->make(SkillService::class),
            $app->make(BattleMessageHandler::class),
            $app->make(CharacterAreaGemEffectService::class),
        ));
        $this->app->bind(CharacterRewardService::class, fn ($app) => new CharacterRewardService(
            $app->make(CharacterXPService::class),
            $app->make(CharacterCurrencyRewardService::class),
            $app->make(SkillService::class),
            $app->make(BuildUniqueItem::class),
            $app->make(BuildMythicItem::class),
            $app->make(BuildCosmicItem::class),
        ));

        $this->app->singleton(BattleRewardMessageContext::class);

        $this->app->bind(FactionHandler::class, function ($app) {
            return new FactionHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(GuideQuestService::class),
                $app->make(BattleMessageHandler::class),
                $app->make(ChanceCalculator::class),
                $app->make(AreaGemEffectService::class),
            );
        });

        $this->app->bind(FactionLoyaltyRewardRequestService::class, function ($app) {
            return new FactionLoyaltyRewardRequestService(
                $app->make(BattleRewardProcessingQueueManager::class),
            );
        });

        $this->app->bind(FactionLoyaltyBountyHandler::class, function ($app) {
            return new FactionLoyaltyBountyHandler(
                $app->make(FactionLoyaltyService::class),
                $app->make(FactionLoyaltyRewardRequestService::class),
            );
        });

        $this->app->bind(GlobalEventParticipation::class, function ($app) {
            return new GlobalEventParticipation(
                $app->make(RandomAffixGenerator::class),
            );
        });

        $this->app->bind(BattleGlobalEventParticipationHandler::class, function ($app) {
            return new BattleGlobalEventParticipationHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(EventGoalsService::class),
                $app->make(GlobalEventGoalProgressionService::class),
            );
        });

        $this->app->bind(PurgatorySmithHouseRewardHandler::class, function ($app) {
            return new PurgatorySmithHouseRewardHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(BattleMessageHandler::class),
                $app->make(RandomNumberGenerator::class),
                $app->make(ChanceCalculator::class),
            );
        });

        $this->app->bind(GoldMinesRewardHandler::class, function ($app) {
            return new GoldMinesRewardHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(BattleMessageHandler::class),
                $app->make(RandomNumberGenerator::class),
                $app->make(ChanceCalculator::class),
            );
        });

        $this->app->bind(TheOldChurchRewardHandler::class, function ($app) {
            return new TheOldChurchRewardHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(BattleMessageHandler::class),
                $app->make(RandomNumberGenerator::class),
                $app->make(ChanceCalculator::class),
            );
        });

        $this->app->bind(LocationSpecialtyHandler::class, function ($app) {
            return new LocationSpecialtyHandler(
                $app->make(RandomAffixGenerator::class),
                $app->make(RandomNumberGenerator::class),
                $app->make(ChanceCalculator::class),
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
                $app->make(BattleRewardLedgerService::class),
                $app->make(BattleRewardMessageContext::class),
                $app->make(RandomAffixGenerator::class),
                $app->make(BroadcastTopsUpdateService::class),
                $app->make(GlobalEventGoalEligibilityService::class),
                $app->make(GemWorldRewardService::class),
                $app->make(MonsterListService::class),
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
