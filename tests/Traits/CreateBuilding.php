<?php

namespace Tests\Traits;

use App\Flare\Models\Building;

trait CreateBuilding {

    public function createBuilding(array $options = []): Building {
        return Building::factory()->create($options);
    }
}
