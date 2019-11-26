<?php

namespace Tests\Traits;

use App\Flare\Models\Inventory;

trait CreateInventory {

    public function createInventory(array $options = []) {
        return factory(Inventory::class)->create($options);
    }
}
