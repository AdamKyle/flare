<?php

namespace Tests\Traits;

use App\Flare\Models\Item;

trait CreateItem {

    public function createItem(array $options = []) {
        return factory(Item::class)->create($options);
    }
}
