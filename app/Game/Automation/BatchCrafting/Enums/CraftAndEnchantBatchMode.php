<?php

namespace App\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingMode;

enum CraftAndEnchantBatchMode: string implements BatchCraftingMode
{
    case AMOUNT = 'amount';
    case EXPERIENCE = 'experience';
    case SET = 'set';

    /**
     * Return the number of logical operations performed per recurring execution window.
     *
     * @return int|null The recurring window size, or null for a continuous mode.
     */
    public function executionWindowSize(): ?int
    {
        return match ($this) {
            self::AMOUNT, self::SET => null,
            self::EXPERIENCE => 23,
        };
    }

    /**
     * Return the Batch Crafting dispositions legal for this craft and enchant mode.
     *
     * @return array<int, BatchCraftingDisposition> The legal dispositions for this mode.
     */
    public function allowedDispositions(): array
    {
        return match ($this) {
            self::AMOUNT, self::SET => [
                BatchCraftingDisposition::KEEP,
                BatchCraftingDisposition::SELL,
                BatchCraftingDisposition::DESTROY,
                BatchCraftingDisposition::LIST,
                BatchCraftingDisposition::DISENCHANT,
            ],
            self::EXPERIENCE => [
                BatchCraftingDisposition::KEEP,
                BatchCraftingDisposition::SELL,
                BatchCraftingDisposition::DESTROY,
                BatchCraftingDisposition::LIST,
                BatchCraftingDisposition::DISENCHANT,
                BatchCraftingDisposition::KEEP_BEST_SELL_REST,
                BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
                BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST,
            ],
        };
    }

    /**
     * Return the Batch Crafting output destinations legal for this craft and enchant mode.
     *
     * Experience always retains into the Crafted Items Set automatically, so it never
     * exposes a user-selected output destination. Amount has no Set-shaped output, so a
     * normal specified Inventory Set is reserved for the Set mode only.
     *
     * @return array<int, BatchCraftingOutputDestination> The legal output destinations for this mode.
     */
    public function allowedOutputDestinations(): array
    {
        return match ($this) {
            self::AMOUNT => [
                BatchCraftingOutputDestination::INVENTORY,
                BatchCraftingOutputDestination::CRAFTED_ITEMS_SET,
            ],
            self::SET => [
                BatchCraftingOutputDestination::INVENTORY,
                BatchCraftingOutputDestination::CRAFTED_ITEMS_SET,
                BatchCraftingOutputDestination::INVENTORY_SET,
            ],
            self::EXPERIENCE => [],
        };
    }
}
