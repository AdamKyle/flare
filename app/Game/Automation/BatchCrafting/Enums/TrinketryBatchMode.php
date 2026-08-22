<?php

namespace App\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingMode;

enum TrinketryBatchMode: string implements BatchCraftingMode
{
    case EXPERIENCE = 'experience';

    /**
     * Return the number of logical operations performed per recurring execution window.
     *
     * @return int|null The recurring window size.
     */
    public function executionWindowSize(): ?int
    {
        return match ($this) {
            self::EXPERIENCE => 6,
        };
    }

    /**
     * Return the Batch Crafting dispositions legal for this Trinketry mode.
     *
     * @return array<int, BatchCraftingDisposition> The legal dispositions for this mode.
     */
    public function allowedDispositions(): array
    {
        return match ($this) {
            self::EXPERIENCE => [
                BatchCraftingDisposition::KEEP,
                BatchCraftingDisposition::DESTROY,
                BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
            ],
        };
    }

    /**
     * Return the Batch Crafting output destinations legal for this Trinketry mode.
     *
     * Kept Trinkets always belong in the Crafted Items Set, so no user-selected
     * output destination applies.
     *
     * @return array<int, BatchCraftingOutputDestination> The legal output destinations for this mode.
     */
    public function allowedOutputDestinations(): array
    {
        return [];
    }
}
