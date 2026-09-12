<?php

namespace App\Game\Automation\BatchCrafting\Values;

use App\Flare\Models\Item;
use App\Game\Skills\Values\CraftingSkillGroup;

class CraftExperienceCycleTarget
{
    public function __construct(
        public readonly CraftingSkillGroup $skillGroup,
        public readonly string $craftingType,
        public readonly ?string $itemType,
    ) {}

    /**
     * Determine whether the given item satisfies this Experience cycle target.
     *
     * @param Item $item The candidate item being checked.
     * @return bool True when the item matches this target.
     */
    public function matches(Item $item): bool
    {
        if (! is_null($this->itemType)) {
            return $item->type === $this->itemType;
        }

        return $item->type === $this->craftingType || $item->default_position === $this->craftingType;
    }
}
