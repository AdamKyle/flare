<?php

namespace App\Game\Automation\FactionLoyalty\Providers;

use App\Game\Automation\FactionLoyalty\Coordinators\FactionLoyaltyAutomationActionCoordinator;
use App\Game\Automation\FactionLoyalty\Coordinators\FactionLoyaltyNpcTaskCoordinator;
use App\Game\Automation\FactionLoyalty\Handlers\AutomatedBountyFightHandler;
use App\Game\Automation\FactionLoyalty\Handlers\AutomatedCraftingHandler;
use App\Game\Automation\FactionLoyalty\Loggers\FactionLoyaltyAutomationCraftingLogger;
use App\Game\Automation\FactionLoyalty\Loggers\FactionLoyaltyAutomationFightLogger;
use App\Game\Automation\FactionLoyalty\Services\FactionLoyaltyAutomationService;
use App\Game\Automation\FactionLoyalty\Services\FactionLoyaltyAutomationWarningService;
use App\Game\Automation\FactionLoyalty\Values\AutomatedCraftingAttemptTracker;
use App\Game\Automation\FactionLoyalty\Values\AutomatedCraftingResult;
use App\Game\Automation\FactionLoyalty\Values\AutomatedFightResult;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Services\CharacterRewardService;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\TraverseService;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\SkillService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register the Faction Loyalty automation services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(FactionLoyaltyAutomationService::class, function ($app) {
            return new FactionLoyaltyAutomationService(
                $app->make(CharacterCacheData::class),
            );
        });

        $this->app->bind(FactionLoyaltyAutomationWarningService::class, function ($app) {
            return new FactionLoyaltyAutomationWarningService(
                $app->make(FactionLoyaltyService::class)
            );
        });

        $this->app->bind(FactionLoyaltyNpcTaskCoordinator::class, function ($app) {
            return new FactionLoyaltyNpcTaskCoordinator(
                $app->make(FactionLoyaltyService::class),
                $app->make(MovementService::class),
                $app->make(TraverseService::class)
            );
        });

        $this->app->bind(FactionLoyaltyAutomationActionCoordinator::class, function () {
            return new FactionLoyaltyAutomationActionCoordinator();
        });

        $this->app->bind(AutomatedCraftingAttemptTracker::class, function () {
            return new AutomatedCraftingAttemptTracker();
        });

        $this->app->bind(AutomatedCraftingResult::class, function () {
            return new AutomatedCraftingResult();
        });

        $this->app->bind(AutomatedFightResult::class, function () {
            return new AutomatedFightResult();
        });

        $this->app->bind(FactionLoyaltyAutomationCraftingLogger::class, function () {
            return new FactionLoyaltyAutomationCraftingLogger();
        });

        $this->app->bind(FactionLoyaltyAutomationFightLogger::class, function () {
            return new FactionLoyaltyAutomationFightLogger();
        });

        $this->app->bind(AutomatedCraftingHandler::class, function ($app) {
            return new AutomatedCraftingHandler(
                $app->make(CraftingService::class),
                $app->make(ShopService::class),
                $app->make(AutomatedCraftingAttemptTracker::class),
                $app->make(AutomatedCraftingResult::class)
            );
        });

        $this->app->bind(AutomatedBountyFightHandler::class, function ($app) {
            return new AutomatedBountyFightHandler(
                $app->make(MonsterFightService::class),
                $app->make(BattleEventHandler::class),
                $app->make(CharacterRewardService::class),
                $app->make(SkillService::class),
                $app->make(AutomatedFightResult::class)
            );
        });
    }
}
