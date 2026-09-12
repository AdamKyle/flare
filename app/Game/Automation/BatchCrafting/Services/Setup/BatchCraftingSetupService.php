<?php

namespace App\Game\Automation\BatchCrafting\Services\Setup;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;

interface BatchCraftingSetupService
{
    /**
     * Determine whether this setup service owns preview/start resolution for the given Batch Crafting type.
     *
     * @param BatchCraftingType $type The requested Batch Crafting type.
     * @return bool True when this setup service owns the given type.
     */
    public function supports(BatchCraftingType $type): bool;

    /**
     * Build the preview payload for the validated request, when the requested mode supports a preview.
     *
     * @param Character $character The character requesting the preview.
     * @param array $validated The validated Batch Crafting request data.
     * @return array|null The preview payload, or null when no preview is available for the requested mode.
     */
    public function preview(Character $character, array $validated): ?array;

    /**
     * Resolve the mode-specific starting progress data and any blockers preventing the start.
     *
     * @param Character $character The character starting the run.
     * @param array $validated The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    public function resolveStart(Character $character, array $validated): array;
}
