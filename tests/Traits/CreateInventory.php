<?php

namespace Tests\Traits;

use App\Flare\Models\Inventory;

trait CreateInventory {

    public function createInventory(array $options = []): Inventory {
        return Inventory::factory()->create($options);
    }
}
