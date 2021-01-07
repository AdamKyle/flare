<?php

namespace Tests\Traits;

use App\Flare\Models\GameBuilding;

trait CreateGameBuilding {

    public function createGameBuilding(array $options = []): GameBuilding {
        return GameBuilding::factory()->create($options);
    }
}
