<?php

namespace App\Game\Automation\BatchCrafting\Factories;

use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Registries\BatchCraftingAttributeRegistry;
use BackedEnum;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class BatchCraftingHandlerFactory
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
     * Resolve the registered Batch Crafting handler for the given type and mode.
     *
     * @param  BatchCraftingType  $type  The Batch Crafting type to resolve.
     * @param  BackedEnum  $mode  The craft mode to resolve.
     * @return BatchCraftingHandler The resolved handler instance.
     */
    public function make(BatchCraftingType $type, BackedEnum $mode): BatchCraftingHandler
    {
        $class = $this->registry->handlerFor($type, $mode);
        $handler = $this->container->make($class);

        if (! $handler instanceof BatchCraftingHandler) {
            throw new InvalidArgumentException($class.' must implement '.BatchCraftingHandler::class.'.');
        }

        return $handler;
    }
}
