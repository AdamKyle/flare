<?php

namespace App\Game\BatchCrafting\Providers;

use App\Game\BatchCrafting\Services\BatchCraftingProcessor;
use App\Game\BatchCrafting\Services\EventBatchEnchantingAffixSelector;
use App\Game\BatchCrafting\Services\BatchCraftingLogger;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Admin\Services\MonitoredBugReportService;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Services\GlobalEventGoalProgressionService;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BatchCraftingProcessor::class, function ($app) {
            return new BatchCraftingProcessor(
                $app->make(CraftingService::class),
                $app->make(AlchemyService::class),
                $app->make(TrinketCraftingService::class),
                $app->make(EnchantingService::class),
                $app->make(HolyItemService::class),
                $app->make(MultiInventoryActionService::class),
                $app->make(UseItemService::class),
                $app->make(BatchCraftingSetService::class),
                $app->make(InventorySetService::class),
                $app->make(HandleUpdatingCraftingGlobalEventGoal::class),
                $app->make(ServerMessageHandler::class),
                $app->make(GlobalEventGoalEligibilityService::class),
                $app->make(EventBatchEnchantingAffixSelector::class),
            );
        });

        $this->app->bind(BatchCraftingService::class, function ($app) {
            return new BatchCraftingService(
                $app->make(BatchCraftingProcessor::class),
                $app->make(CraftingService::class),
                $app->make(BatchCraftingLogger::class),
                $app->make(EnchantingService::class),
                $app->make(BatchCraftingSetService::class),
                $app->make(HolyItemService::class),
                $app->make(GlobalEventGoalEligibilityService::class),
                $app->make(GlobalEventGoalProgressionService::class),
                $app->make(MonitoredBugReportService::class),
            );
        });
    }
}
