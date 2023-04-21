<?php

namespace Tests\Traits;

use App\Flare\Models\InventorySlot;

trait CreateInventorySlot {

    public function createInventorySlot(array $options = []): InventorySlot {
        return InventorySlot::factory()->create($options);
    }
}
