<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingMode;
use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Enums\EnchantingBatchMode;
use App\Game\Automation\BatchCrafting\Enums\HolyOilsBatchMode;
use App\Game\Automation\BatchCrafting\Enums\TrinketryBatchMode;

class BatchCraftingModeService
{
    /**
     * Resolve the typed mode enum for the given Batch Crafting type and persisted mode value.
     *
     * @param  BatchCraftingType  $type  The Batch Crafting type.
     * @param  string  $mode  The persisted mode value.
     * @return BatchCraftingMode The resolved typed mode contract.
     */
    public function resolve(BatchCraftingType $type, string $mode): BatchCraftingMode
    {
        return match ($type) {
            BatchCraftingType::CRAFT => CraftingBatchMode::from($mode),
            BatchCraftingType::CRAFT_AND_ENCHANT => CraftAndEnchantBatchMode::from($mode),
            BatchCraftingType::ENCHANT => EnchantingBatchMode::from($mode),
            BatchCraftingType::ALCHEMY => AlchemyBatchMode::from($mode),
            BatchCraftingType::HOLY_OILS => HolyOilsBatchMode::from($mode),
            BatchCraftingType::TRINKETRY => TrinketryBatchMode::from($mode),
        };
    }

    /**
     * Return the recurring execution window size for the given Batch Crafting type and mode.
     *
     * @param  BatchCraftingType  $type  The Batch Crafting type.
     * @param  string  $mode  The persisted mode value.
     * @return int|null The recurring window size, or null for a continuous mode.
     */
    public function executionWindowSize(BatchCraftingType $type, string $mode): ?int
    {
        return $this->resolve($type, $mode)->executionWindowSize();
    }

    /**
     * Return the legal dispositions for the given Batch Crafting type and mode.
     *
     * @param  BatchCraftingType  $type  The Batch Crafting type.
     * @param  string  $mode  The persisted mode value.
     * @return array<int, BatchCraftingDisposition> The legal dispositions.
     */
    public function allowedDispositions(BatchCraftingType $type, string $mode): array
    {
        return $this->resolve($type, $mode)->allowedDispositions();
    }

    /**
     * Return the legal output destinations for the given Batch Crafting type and mode.
     *
     * @param  BatchCraftingType  $type  The Batch Crafting type.
     * @param  string  $mode  The persisted mode value.
     * @return array<int, BatchCraftingOutputDestination> The legal output destinations.
     */
    public function allowedOutputDestinations(BatchCraftingType $type, string $mode): array
    {
        return $this->resolve($type, $mode)->allowedOutputDestinations();
    }
}
