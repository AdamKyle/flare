<?php

namespace App\Game\Automation\BatchCrafting\Providers;

use App\Game\Automation\BatchCrafting\Handlers\CraftAmountHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftEventHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftExperienceHandler;
use App\Game\Automation\BatchCrafting\Handlers\CraftSetHandler;
use App\Game\Automation\BatchCrafting\Orchestrators\CraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Registries\BatchCraftingAttributeRegistry;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register the Batch Crafting attribute registry.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BatchCraftingAttributeRegistry::class, function () {
            return new BatchCraftingAttributeRegistry(
                [CraftingOrchestrator::class],
                [
                    CraftAmountHandler::class,
                    CraftExperienceHandler::class,
                    CraftSetHandler::class,
                    CraftEventHandler::class,
                ],
            );
        });
    }
}
