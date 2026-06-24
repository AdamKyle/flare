<?php

namespace App\Game\Automation\Providers;

use App\Flare\Services\CharacterRewardService;
use App\Game\Automation\Coordinators\FactionLoyaltyAutomationActionCoordinator;
use App\Game\Automation\Coordinators\FactionLoyaltyNpcTaskCoordinator;
use App\Game\Automation\Handlers\AutomatedBountyFightHandler;
use App\Game\Automation\Handlers\AutomatedCraftingHandler;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationCraftingLogger;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationFightLogger;
use App\Game\Automation\Middleware\IsCharacterExploring;
use App\Game\Automation\Services\DelveExplorationAutomationService;
use App\Game\Automation\Services\DelveStatusService;
use App\Game\Automation\Services\ExplorationAutomationService;
use App\Game\Automation\Services\ExplorationCreatureCountCalculator;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\Automation\Services\ExplorationWarningService;
use App\Game\Automation\Services\FactionLoyaltyAutomationService;
use App\Game\Automation\Services\FactionLoyaltyAutomationWarningService;
use App\Game\Automation\Values\AutomatedCraftingAttemptTracker;
use App\Game\Automation\Values\AutomatedCraftingResult;
use App\Game\Automation\Values\AutomatedFightResult;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
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
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(ExplorationAutomationService::class, function ($app) {
            return new ExplorationAutomationService(
                $app->make(CharacterCacheData::class),
                $app->make(ExplorationCreatureCountCalculator::class),
                $app->make(ExplorationLogService::class),
                $app->make(ExplorationWarningService::class)
            );
        });

        $this->app->bind(DelveExplorationAutomationService::class, function ($app) {
            return new DelveExplorationAutomationService(
                $app->make(CharacterCacheData::class)
            );
        });

        $this->app->bind(DelveStatusService::class, function () {
            return new DelveStatusService;
        });

        $this->app->bind(FactionLoyaltyAutomationService::class, function ($app) {
            return new FactionLoyaltyAutomationService(
                $app->make(CharacterCacheData::class)
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

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('is.character.exploring', IsCharacterExploring::class);
    }
}
