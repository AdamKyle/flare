<?php

namespace App\Game\Automation\BatchCrafting\Services\Setup;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Skills\Services\TrinketCraftingService;

class TrinketryBatchCraftingSetupService implements BatchCraftingSetupService
{
    public function __construct(private readonly TrinketCraftingService $trinketCraftingService) {}

    /**
     * Determine whether this setup service owns preview/start resolution for the given Batch Crafting type.
     *
     * @param BatchCraftingType $type The requested Batch Crafting type.
     * @return bool True when this setup service owns the given type.
     */
    public function supports(BatchCraftingType $type): bool
    {
        return $type === BatchCraftingType::TRINKETRY;
    }

    /**
     * Build the preview payload for the validated request.
     *
     * Trinketry has no item selector, amount, or output destination, so no preview applies.
     *
     * @param Character $character The character requesting the preview.
     * @param array $validated The validated Batch Crafting request data.
     * @return array|null Always null.
     */
    public function preview(Character $character, array $validated): ?array
    {
        return null;
    }

    /**
     * Resolve the starting progress data and blockers for a Trinketry run.
     *
     * @param Character $character The character starting the run.
     * @param array $validated The validated Batch Crafting request data.
     * @return array{progress: array, blockers: array<int, string>} The starting progress data and any blockers.
     */
    public function resolveStart(Character $character, array $validated): array
    {
        $blockers = [];

        if (is_null($this->trinketCraftingService->findMeaningfulBatchItem($character))) {
            $blockers[] = 'There is no Trinket that can still provide meaningful XP.';
        }

        $progress = [
            'trinketry_mode' => $validated['progress']['trinketry_mode'],
            'trinketry_xp_gained' => 0,
            'current_item_id' => null,
            'current_item_name' => null,
        ];

        return ['progress' => $progress, 'blockers' => $blockers];
    }
}
