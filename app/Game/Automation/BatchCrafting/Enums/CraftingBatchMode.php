<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum CraftingBatchMode: string
{
    case AMOUNT = 'specific_item';
    case EXPERIENCE = 'experience';
    case SET = 'craft_set';
    case EVENT = 'event';

    /**
     * Return the number of logical operations performed per recurring execution window.
     *
     * @return int|null The recurring window size, or null for a continuous mode.
     */
    public function executionWindowSize(): ?int
    {
        return match ($this) {
            self::AMOUNT, self::SET => null,
            self::EXPERIENCE => 6,
            self::EVENT => 23,
        };
    }

    /**
     * Return the Batch Crafting dispositions legal for this craft mode.
     *
     * @return array<int, BatchCraftingDisposition> The legal dispositions for this craft mode.
     */
    public function allowedDispositions(): array
    {
        return match ($this) {
            self::AMOUNT, self::SET => [BatchCraftingDisposition::KEEP, BatchCraftingDisposition::SELL, BatchCraftingDisposition::DESTROY],
            self::EXPERIENCE => [BatchCraftingDisposition::KEEP, BatchCraftingDisposition::SELL, BatchCraftingDisposition::DESTROY, BatchCraftingDisposition::KEEP_BEST_SELL_REST, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST],
            self::EVENT => [BatchCraftingDisposition::KEEP],
        };
    }

    /**
     * Return the Batch Crafting output destinations legal for this craft mode.
     *
     * Only Craft Amount and Craft Set support a Keep disposition with a retained output
     * destination; Craft For Experience and Craft For Event never reach this check.
     *
     * @return array<int, BatchCraftingOutputDestination> The legal output destinations for this craft mode.
     */
    public function allowedOutputDestinations(): array
    {
        return match ($this) {
            self::AMOUNT => [BatchCraftingOutputDestination::INVENTORY, BatchCraftingOutputDestination::CRAFTED_ITEMS_SET],
            self::SET => [BatchCraftingOutputDestination::INVENTORY, BatchCraftingOutputDestination::CRAFTED_ITEMS_SET, BatchCraftingOutputDestination::INVENTORY_SET],
            self::EXPERIENCE, self::EVENT => [],
        };
    }
}
