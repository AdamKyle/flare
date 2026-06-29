<?php

namespace Tests\Traits;

use App\Flare\Models\AlchemyBagSlot;

trait CreateAlchemyBagSlot
{
    public function createAlchemyBagSlot(array $options = []): AlchemyBagSlot
    {
        return AlchemyBagSlot::factory()->create($options);
    }
}
