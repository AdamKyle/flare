<?php

namespace Tests\Traits;

use App\Flare\Models\GlobalEventCraftingInventory;

trait CreateGlobalCraftingInventory
{

    public function createGlobalCraftingInventory(array $options = []): GlobalEventCraftingInventory
    {
        return GlobalEventCraftingInventory::factory()->create($options);
    }
}
