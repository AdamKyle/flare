<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Skills\Services\CraftingService;

class CraftSetHandRecommendationService
{
    /**
     * @param  CraftingService  $craftingService
     */
    public function __construct(
        private readonly CraftingService $craftingService,
    ) {}

    /**
     * Recommend the best currently craftable item for one explicitly selected Craft Set hand type.
     *
     * Backend Craft Set hand pair validation remains authoritative elsewhere; this
     * service only resolves a single hand's default recommendation.
     *
     * @param  Character  $character  The character requesting the recommendation.
     * @param  string  $handType  The selected hand type: a valid weapon type, or "shield".
     * @return Item|null The recommended item, or null when the hand type is unsupported or nothing is craftable.
     */
    public function recommend(Character $character, string $handType): ?Item
    {
        if ($handType === 'shield') {
            return $this->craftingService->findBestCraftableShieldForAutomation($character);
        }

        if (! in_array($handType, ItemType::validWeapons(), true)) {
            return null;
        }

        return $this->craftingService->findBestCraftableWeaponForAutomation($character, $handType);
    }
}
