<?php

namespace Tests\Traits;

use App\Flare\Models\ItemAffix;

trait CreateItemAffix {

    public function createItemAffix(array $options = []): ItemAffix {
        return ItemAffix::factory()->create($options);
    }
}
