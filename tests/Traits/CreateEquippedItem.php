<?php

namespace Tests\Traits;

use App\Flare\Models\EquippedItem;

trait CreateEquippedItem {

    public function createEquippedItem(array $options = []): Drop {
        return factory(EquippedItem::class)->create($options);
    }
}
