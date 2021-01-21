<?php

namespace Tests\Traits;

use App\Flare\Models\GameUnit;

trait CreateGameUnit {

    public function createGameUnit(array $options = []): GameUnit {
        return GameUnit::factory()->create($options);
    }
}
