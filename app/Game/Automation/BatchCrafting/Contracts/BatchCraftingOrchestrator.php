<?php

namespace App\Game\Automation\BatchCrafting\Contracts;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;

interface BatchCraftingOrchestrator
{
    /**
     * Coordinate the active Batch Crafting run for the character to its next operation.
     *
     * @param  BatchCrafting  $batchCrafting  The active Batch Crafting record.
     * @param  Character  $character  The character crafting.
     * @return BatchCraftingOperationResult The operation outcome.
     */
    public function orchestrate(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult;
}
