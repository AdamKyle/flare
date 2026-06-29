<?php

namespace Tests\Traits;

use App\Flare\Models\AlchemyBag;

trait CreateAlchemyBag
{
    public function createAlchemyBag(array $options = []): AlchemyBag
    {
        return AlchemyBag::factory()->create($options);
    }
}
