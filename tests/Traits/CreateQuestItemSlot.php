<?php

namespace Tests\Traits;

use App\Flare\Models\QuestItemSlot;

trait CreateInventorySlot {

    public function createInventorySlot(array $options = []): InventorySlot {
        return factory(QuestItemSlot::class)->create($options);
    }
}
