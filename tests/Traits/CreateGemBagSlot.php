<?php

namespace Tests\Traits;

use App\Flare\Models\GemBagSlot;

trait CreateGemBagSlot
{
    public function createGemBagSlot(array $options = []): GemBagSlot
    {
        return GemBagSlot::create($options);
    }
}
