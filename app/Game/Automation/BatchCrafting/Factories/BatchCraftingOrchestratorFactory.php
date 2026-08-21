<?php

namespace App\Game\Automation\BatchCrafting\Factories;

use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Registries\BatchCraftingAttributeRegistry;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class BatchCraftingOrchestratorFactory
{
    /**
     * @param  BatchCraftingAttributeRegistry  $registry
     * @param  Container  $container
     */
    public function __construct(
        private readonly BatchCraftingAttributeRegistry $registry,
        private readonly Container $container,
    ) {}

    /**
     * Resolve the registered Batch Crafting orchestrator for the given type.
     *
     * @param  BatchCraftingType  $type  The Batch Crafting type to resolve.
     * @return BatchCraftingOrchestrator The resolved orchestrator instance.
     */
    public function make(BatchCraftingType $type): BatchCraftingOrchestrator
    {
        $class = $this->registry->orchestratorFor($type);
        $orchestrator = $this->container->make($class);

        if (! $orchestrator instanceof BatchCraftingOrchestrator) {
            throw new InvalidArgumentException($class.' must implement '.BatchCraftingOrchestrator::class.'.');
        }

        return $orchestrator;
    }
}
