<?php

namespace App\Game\Gems\Contracts;

use App\Game\Gems\Values\DuplicateGemAbilityReport;

interface GemAbilityDuplicateQuery
{
    /**
     * Return duplicate Active Gem Abilities socketed within a single Item.
     *
     * @param int $itemId
     * @return DuplicateGemAbilityReport
     */
    public function forItem(int $itemId): DuplicateGemAbilityReport;

    /**
     * Return the Character's duplicate Active Gem Abilities that include the given owned Item.
     *
     * @param int $characterId
     * @param int $itemId
     * @return DuplicateGemAbilityReport
     */
    public function forOwnedItem(int $characterId, int $itemId): DuplicateGemAbilityReport;

    /**
     * Return the Character's duplicate Active Gem Abilities that include an Item from the given Inventory Set.
     *
     * @param int $characterId
     * @param int $inventorySetId
     * @return DuplicateGemAbilityReport
     */
    public function forInventorySet(int $characterId, int $inventorySetId): DuplicateGemAbilityReport;
}
