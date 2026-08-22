<?php

namespace App\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingMode;

enum AlchemyBatchMode: string implements BatchCraftingMode
{
    case AMOUNT = 'amount';
    case EXPERIENCE = 'experience';

    /**
     * Return the number of logical operations performed per recurring execution window.
     *
     * @return int|null The recurring window size, or null for a continuous mode.
     */
    public function executionWindowSize(): ?int
    {
        return match ($this) {
            self::AMOUNT => null,
            self::EXPERIENCE => 6,
        };
    }

    /**
     * Return the Batch Crafting dispositions legal for this Alchemy mode.
     *
     * @return array<int, BatchCraftingDisposition> The legal dispositions for this mode.
     */
    public function allowedDispositions(): array
    {
        return match ($this) {
            self::AMOUNT => [
                BatchCraftingDisposition::KEEP,
                BatchCraftingDisposition::DESTROY,
                BatchCraftingDisposition::LIST,
                BatchCraftingDisposition::USE_NOW,
            ],
            self::EXPERIENCE => [
                BatchCraftingDisposition::KEEP,
                BatchCraftingDisposition::DESTROY,
                BatchCraftingDisposition::LIST,
                BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
                BatchCraftingDisposition::USE_NOW,
            ],
        };
    }

    /**
     * Return the Batch Crafting output destinations legal for this Alchemy mode.
     *
     * Retained Alchemy items always remain Alchemy Bag-owned, so Alchemy never exposes
     * a user-selected output destination.
     *
     * @return array<int, BatchCraftingOutputDestination> The legal output destinations for this mode.
     */
    public function allowedOutputDestinations(): array
    {
        return [];
    }
}
