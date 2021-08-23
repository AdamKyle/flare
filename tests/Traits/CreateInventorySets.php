<?php

namespace Tests\Traits;

use App\Flare\Models\InventorySet;

trait CreateInventorySets {

    public function createInventorySet(array $options = []): InventorySet {
        return InventorySet::factory()->create($options);
    }
}
