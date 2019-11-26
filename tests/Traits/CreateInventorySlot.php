<?php

namespace Tests\Traits;

use App\Flare\Models\InventorySlot;

trait CreateInventorySlot {

    public function createInventorySlot(array $options = []) {
        return factory(InventorySlot::class)->create($options);
    }
}
