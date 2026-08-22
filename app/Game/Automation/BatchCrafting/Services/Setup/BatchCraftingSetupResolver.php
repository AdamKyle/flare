<?php

namespace App\Game\Automation\BatchCrafting\Services\Setup;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use InvalidArgumentException;

class BatchCraftingSetupResolver
{
    /**
     * @param  array<int, BatchCraftingSetupService>  $setupServices  The registered setup services.
     */
    public function __construct(private readonly array $setupServices) {}

    /**
     * Resolve the registered setup service for the given Batch Crafting type.
     *
     * @param  BatchCraftingType  $type  The requested Batch Crafting type.
     * @return BatchCraftingSetupService The resolved setup service.
     */
    public function resolve(BatchCraftingType $type): BatchCraftingSetupService
    {
        foreach ($this->setupServices as $setupService) {
            if ($setupService->supports($type)) {
                return $setupService;
            }
        }

        throw new InvalidArgumentException('No BatchCraftingSetupService is registered for batch crafting type ['.$type->value.'].');
    }
}
