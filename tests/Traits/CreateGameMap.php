<?php

namespace Tests\Traits;

use App\Flare\Models\GameMap;

trait CreateGameMap {

    public function createGameMap(array $options = []): GameMap {
        return GameMap::factory()->create($options);
    }
}
