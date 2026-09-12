<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\CraftEventTargetType;
use App\Game\Skills\Services\CraftingService;

class CraftEventTargetService
{
    public function __construct(
        private readonly CraftingService $craftingService,
    ) {}

    /**
     * Return the authoritative Craft For Event cycle size, derived from the real target sequence.
     *
     * @return int The number of targets in one full Event cycle.
     */
    public function cycleSize(): int
    {
        return count(CraftEventTargetType::orderedCases());
    }

    /**
     * Resolve the Craft For Event target for the given persisted cycle position.
     *
     * @param int $cyclePosition The persisted Event cycle position.
     * @return CraftEventTargetType The resolved target for this cycle position.
     */
    public function resolveTarget(int $cyclePosition): CraftEventTargetType
    {
        return CraftEventTargetType::orderedCases()[$cyclePosition % $this->cycleSize()];
    }

    /**
     * Resolve a currently craftable, inexpensive item for the resolved Event target.
     *
     * @param Character $character The character running the batch.
     * @param CraftEventTargetType $target The resolved cycle target.
     * @return Item|null The resolved item, or null when no eligible target item can currently resolve.
     */
    public function resolveTargetItem(Character $character, CraftEventTargetType $target): ?Item
    {
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, $target->skillGroup()->value);

        if (is_null($skill)) {
            return null;
        }

        if ($target === CraftEventTargetType::WEAPON) {
            return $this->craftingService->findInexpensiveCraftableWeaponForAutomation($skill);
        }

        return $this->craftingService->findInexpensiveCraftableItem($skill, $target->craftingType(), $target->itemType());
    }
}
