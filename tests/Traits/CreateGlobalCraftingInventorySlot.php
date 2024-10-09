<?php

namespace Tests\Traits;

use App\Flare\Models\GlobalEventCraftingInventorySlot;

trait CreateGlobalCraftingInventorySlot
{

    public function createGlobalCraftingInventorySlot(array $options = []): GlobalEventCraftingInventorySlot
    {
        return GlobalEventCraftingInventorySlot::factory()->create($options);
    }
}
