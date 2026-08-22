<?php

namespace App\Game\Automation\BatchCrafting\Values;

use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\Item;

class ResolvedEventEnchantTarget
{
    public function __construct(
        public readonly GlobalEventCraftingInventorySlot $slot,
        public readonly Item $item,
    ) {}
}
