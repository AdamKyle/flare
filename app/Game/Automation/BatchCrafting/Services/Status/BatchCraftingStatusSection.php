<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;

interface BatchCraftingStatusSection
{
    /**
     * Determine whether this section builds the mode-specific status facts for the given type and mode.
     *
     * @param BatchCraftingType $type The batch's Batch Crafting type.
     * @param string $mode The batch's persisted mode value.
     * @return bool True when this section owns the given type and mode.
     */
    public function supports(BatchCraftingType $type, string $mode): bool;

    /**
     * Build the mode-specific status facts for the batch.
     *
     * @param Character $character The character the batch belongs to.
     * @param BatchCrafting $batchCrafting The visible Batch Crafting record.
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array The mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array;
}
