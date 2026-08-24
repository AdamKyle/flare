<?php

namespace Tests\Traits;

use App\Flare\Models\MaxLevelConfiguration;

trait CreateMaxLevelConfiguration
{
    public function createMaxLevelConfiguration(array $options = []): MaxLevelConfiguration
    {
        return MaxLevelConfiguration::factory()->create($options);
    }
}
