<?php

namespace Tests\Traits;

use App\Flare\Models\Item;

trait CreateItem
{
    public function createItem(array $options = []): Item
    {
        return Item::factory()->create($options);
    }
}
