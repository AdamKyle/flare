<?php

namespace App\Game\Automation\BatchCrafting\Providers;

use App\Game\Automation\BatchCrafting\Handlers\AlchemyAmountHandler;
use App\Game\Automation\BatchCrafting\Handlers\AlchemyExperienceHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftAmountHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftAndEnchantAmountHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftAndEnchantExperienceHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftAndEnchantSetHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftEventHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftExperienceHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftSetHandler;
use App\Game\Automation\BatchCrafting\Handlers\EventEnchantHandler;
use App\Game\Automation\BatchCrafting\Handlers\HolyOilSelectedItemsHandler;
use App\Game\Automation\BatchCrafting\Handlers\HolyOilSetHandler;
use App\Game\Automation\BatchCrafting\Handlers\TrinketryHandler;
use App\Game\Automation\BatchCrafting\Orchestrators\AlchemyOrchestrator;
use App\Game\Automation\BatchCrafting\Orchestrators\CraftAndEnchantOrchestrator;
use App\Game\Automation\BatchCrafting\Orchestrators\CraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Orchestrators\EnchantingOrchestrator;
use App\Game\Automation\BatchCrafting\Orchestrators\HolyOilsOrchestrator;
use App\Game\Automation\BatchCrafting\Orchestrators\TrinketryOrchestrator;
use App\Game\Automation\BatchCrafting\Registries\BatchCraftingAttributeRegistry;
use App\Game\Automation\BatchCrafting\Services\Setup\AlchemyBatchCraftingSetupService;
use App\Game\Automation\BatchCrafting\Services\Setup\BatchCraftingSetupResolver;
use App\Game\Automation\BatchCrafting\Services\Setup\CraftAndEnchantBatchCraftingSetupService;
use App\Game\Automation\BatchCrafting\Services\Setup\CraftBatchCraftingSetupService;
use App\Game\Automation\BatchCrafting\Services\Setup\EnchantBatchCraftingSetupService;
use App\Game\Automation\BatchCrafting\Services\Setup\HolyOilsBatchCraftingSetupService;
use App\Game\Automation\BatchCrafting\Services\Setup\TrinketryBatchCraftingSetupService;
use App\Game\Automation\BatchCrafting\Services\Status\AlchemyAmountStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\AlchemyExperienceStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\BatchCraftingStatusSectionResolver;
use App\Game\Automation\BatchCrafting\Services\Status\CraftAmountStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\CraftAndEnchantAmountStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\CraftAndEnchantExperienceStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\CraftAndEnchantSetStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\CraftEventStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\CraftExperienceStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\CraftSetStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\EnchantEventStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\HolyOilSelectedItemsStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\HolyOilSetStatusSection;
use App\Game\Automation\BatchCrafting\Services\Status\TrinketryStatusSection;
use App\Game\Automation\BatchCrafting\Validation\AlchemyBatchCraftingRules;
use App\Game\Automation\BatchCrafting\Validation\BatchCraftingRuleResolver;
use App\Game\Automation\BatchCrafting\Validation\CraftAndEnchantBatchCraftingRules;
use App\Game\Automation\BatchCrafting\Validation\CraftBatchCraftingRules;
use App\Game\Automation\BatchCrafting\Validation\EnchantBatchCraftingRules;
use App\Game\Automation\BatchCrafting\Validation\HolyOilsBatchCraftingRules;
use App\Game\Automation\BatchCrafting\Validation\TrinketryBatchCraftingRules;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register the Batch Crafting attribute registry, setup resolver, status section resolver, and rule resolver.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BatchCraftingAttributeRegistry::class, function () {
            return new BatchCraftingAttributeRegistry(
                [
                    CraftingOrchestrator::class,
                    CraftAndEnchantOrchestrator::class,
                    EnchantingOrchestrator::class,
                    AlchemyOrchestrator::class,
                    HolyOilsOrchestrator::class,
                    TrinketryOrchestrator::class,
                ],
                [
                    CraftAmountHandler::class,
                    CraftExperienceHandler::class,
                    CraftSetHandler::class,
                    CraftEventHandler::class,
                    CraftAndEnchantAmountHandler::class,
                    CraftAndEnchantExperienceHandler::class,
                    CraftAndEnchantSetHandler::class,
                    EventEnchantHandler::class,
                    AlchemyAmountHandler::class,
                    AlchemyExperienceHandler::class,
                    HolyOilSelectedItemsHandler::class,
                    HolyOilSetHandler::class,
                    TrinketryHandler::class,
                ],
            );
        });

        $this->app->singleton(BatchCraftingSetupResolver::class, function ($app) {
            return new BatchCraftingSetupResolver([
                $app->make(CraftBatchCraftingSetupService::class),
                $app->make(CraftAndEnchantBatchCraftingSetupService::class),
                $app->make(EnchantBatchCraftingSetupService::class),
                $app->make(AlchemyBatchCraftingSetupService::class),
                $app->make(HolyOilsBatchCraftingSetupService::class),
                $app->make(TrinketryBatchCraftingSetupService::class),
            ]);
        });

        $this->app->singleton(BatchCraftingStatusSectionResolver::class, function ($app) {
            return new BatchCraftingStatusSectionResolver([
                $app->make(CraftAmountStatusSection::class),
                $app->make(CraftExperienceStatusSection::class),
                $app->make(CraftSetStatusSection::class),
                $app->make(CraftEventStatusSection::class),
                $app->make(CraftAndEnchantAmountStatusSection::class),
                $app->make(CraftAndEnchantExperienceStatusSection::class),
                $app->make(CraftAndEnchantSetStatusSection::class),
                $app->make(EnchantEventStatusSection::class),
                $app->make(AlchemyAmountStatusSection::class),
                $app->make(AlchemyExperienceStatusSection::class),
                $app->make(HolyOilSelectedItemsStatusSection::class),
                $app->make(HolyOilSetStatusSection::class),
                $app->make(TrinketryStatusSection::class),
            ]);
        });

        $this->app->singleton(BatchCraftingRuleResolver::class, function ($app) {
            return new BatchCraftingRuleResolver(
                $app->make(CraftBatchCraftingRules::class),
                $app->make(CraftAndEnchantBatchCraftingRules::class),
                $app->make(EnchantBatchCraftingRules::class),
                $app->make(AlchemyBatchCraftingRules::class),
                $app->make(HolyOilsBatchCraftingRules::class),
                $app->make(TrinketryBatchCraftingRules::class),
            );
        });
    }
}
