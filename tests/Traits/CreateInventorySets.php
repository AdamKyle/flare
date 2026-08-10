<?php

namespace Tests\Traits;

use App\Flare\Models\InventorySet;
use App\Flare\Models\SetSlot;

trait CreateInventorySets
{
    public function createInventorySet(array $options = []): InventorySet
    {
        return InventorySet::factory()->create($options);
    }

    public function createInventorySetSlot(array $options = []): SetSlot
    {
        return SetSlot::factory()->create($options);
    }

    public function fillInventorySetSlots(InventorySet $set, int $count, int $itemId): void
    {
        SetSlot::factory()
            ->count($count)
            ->create([
                'inventory_set_id' => $set->id,
                'item_id' => $itemId,
            ]);
    }
}
