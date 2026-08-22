<?php

namespace App\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingMode;

enum EnchantingBatchMode: string implements BatchCraftingMode
{
    case EVENT = 'event';

    /**
     * Return the number of logical operations performed per recurring execution window.
     *
     * @return int|null The recurring window size, or null for a continuous mode.
     */
    public function executionWindowSize(): ?int
    {
        return match ($this) {
            self::EVENT => 23,
        };
    }

    /**
     * Return the Batch Crafting dispositions legal for this enchanting mode.
     *
     * @return array<int, BatchCraftingDisposition> The legal dispositions for this mode.
     */
    public function allowedDispositions(): array
    {
        return match ($this) {
            self::EVENT => [BatchCraftingDisposition::KEEP],
        };
    }

    /**
     * Return the Batch Crafting output destinations legal for this enchanting mode.
     *
     * @return array<int, BatchCraftingOutputDestination> The legal output destinations for this mode.
     */
    public function allowedOutputDestinations(): array
    {
        return match ($this) {
            self::EVENT => [],
        };
    }
}
