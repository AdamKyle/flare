<?php

namespace App\Game\Automation\BatchCrafting\Contracts;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;

interface BatchCraftingMode
{
    /**
     * Return the number of logical operations performed per recurring execution window.
     *
     * @return int|null The recurring window size, or null for a continuous mode.
     */
    public function executionWindowSize(): ?int;

    /**
     * Return the Batch Crafting dispositions legal for this mode.
     *
     * @return array<int, BatchCraftingDisposition> The legal dispositions for this mode.
     */
    public function allowedDispositions(): array;

    /**
     * Return the Batch Crafting output destinations legal for this mode.
     *
     * @return array<int, BatchCraftingOutputDestination> The legal output destinations for this mode.
     */
    public function allowedOutputDestinations(): array;
}
