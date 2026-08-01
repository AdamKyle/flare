<?php

namespace Tests\Traits;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Collection;

trait CreateItem
{
    public function createItem(array $options = []): Item
    {
        return Item::factory()->create($options);
    }

    public function createCraftableEquipmentItems(array $options = []): Collection
    {
        return Item::factory()
            ->state(array_merge([
                'can_craft' => true,
                'skill_level_required' => 0,
            ], $options))
            ->sequence(
                ['type' => 'body', 'crafting_type' => 'armour'],
                ['type' => 'leggings', 'crafting_type' => 'armour'],
                ['type' => 'sleeves', 'crafting_type' => 'armour'],
                ['type' => 'gloves', 'crafting_type' => 'armour'],
                ['type' => 'feet', 'crafting_type' => 'armour'],
                ['type' => 'helmet', 'crafting_type' => 'armour'],
                ['type' => 'ring', 'crafting_type' => 'ring'],
                ['type' => 'ring', 'crafting_type' => 'ring'],
                ['type' => 'spell-damage', 'crafting_type' => 'spell'],
                ['type' => 'spell-healing', 'crafting_type' => 'spell'],
            )
            ->count(10)
            ->create();
    }
}
