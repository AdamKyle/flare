<?php

namespace App\Game\Automation\BatchCrafting\Orchestrators;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingType;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Factories\BatchCraftingHandlerFactory;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;

#[HandlesBatchCraftingType(BatchCraftingType::ALCHEMY)]
class AlchemyOrchestrator implements BatchCraftingOrchestrator
{
    public function __construct(private readonly BatchCraftingHandlerFactory $handlerFactory) {}

    /**
     * Resolve and run the Alchemy batch type's handler for the current mode.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the handled operation.
     */
    public function orchestrate(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;
        $mode = AlchemyBatchMode::from($progress['alchemy_mode']);

        $handler = $this->handlerFactory->make(BatchCraftingType::ALCHEMY, $mode);

        return $handler->handle($batchCrafting, $character);
    }
}
