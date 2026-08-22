<?php

namespace App\Game\Automation\BatchCrafting\Values;

use App\Flare\Models\Item;
use App\Game\Skills\Values\CraftingSkillGroup;

class ResolvedCraftAndEnchantExperienceTarget
{
    public function __construct(
        public readonly Item $item,
        public readonly CraftingSkillGroup $craftingSkillGroup,
        public readonly int $nextCyclePosition,
    ) {}
}
