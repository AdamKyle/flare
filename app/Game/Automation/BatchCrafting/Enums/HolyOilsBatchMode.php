<?php

namespace App\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingMode;

enum HolyOilsBatchMode: string implements BatchCraftingMode
{
    case SELECTED_ITEMS = 'selected_items';
    case INVENTORY_SET = 'inventory_set';

    /**
     * Return the number of logical operations performed per recurring execution window.
     *
     * Both Holy Oil modes are continuous after the initial minute.
     *
     * @return int|null Always null.
     */
    public function executionWindowSize(): ?int
    {
        return null;
    }

    /**
     * Return the Batch Crafting dispositions legal for this Holy Oils mode.
     *
     * @return array<int, BatchCraftingDisposition> The legal dispositions for this mode.
     */
    public function allowedDispositions(): array
    {
        return [
            BatchCraftingDisposition::KEEP,
            BatchCraftingDisposition::SELL,
            BatchCraftingDisposition::DESTROY,
            BatchCraftingDisposition::LIST,
            BatchCraftingDisposition::DISENCHANT,
        ];
    }

    /**
     * Return the Batch Crafting output destinations legal for this Holy Oils mode.
     *
     * Holy Oils modify existing target items in place, so no output destination applies.
     *
     * @return array<int, BatchCraftingOutputDestination> The legal output destinations for this mode.
     */
    public function allowedOutputDestinations(): array
    {
        return [];
    }
}
