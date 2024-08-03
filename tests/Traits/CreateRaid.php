<?php

namespace Tests\Traits;

use App\Flare\Models\Raid;

trait CreateRaid
{
    public function createRaid(array $options = []): Raid
    {
        return Raid::factory()->create($options);
    }
}
