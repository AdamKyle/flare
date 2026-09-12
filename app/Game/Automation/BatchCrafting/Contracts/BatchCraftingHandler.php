<?php

namespace App\Game\Automation\BatchCrafting\Contracts;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;

interface BatchCraftingHandler
{
    /**
     * Execute one Batch Crafting operation for the active batch and character.
     *
     * @param BatchCrafting $batchCrafting The active Batch Crafting record.
     * @param Character $character The character crafting.
     * @return BatchCraftingOperationResult The operation outcome.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult;
}
