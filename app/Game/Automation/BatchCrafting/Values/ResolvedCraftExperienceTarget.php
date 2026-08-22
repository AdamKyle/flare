<?php

namespace App\Game\Automation\BatchCrafting\Values;

use App\Flare\Models\Item;

class ResolvedCraftExperienceTarget
{
    public function __construct(
        public readonly int $index,
        public readonly CraftExperienceCycleTarget $target,
        public readonly Item $item,
    ) {}
}
