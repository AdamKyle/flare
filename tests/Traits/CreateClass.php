<?php

namespace Tests\Traits;

use App\Flare\Models\GameClass;

trait CreateClass
{
    public function createClass(array $options = []): GameClass
    {
        return GameClass::factory()->create($options);
    }
}
