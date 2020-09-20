<?php

namespace Tests\Traits;

use App\Flare\Models\GameRace;

trait CreateRace {

    public function createRace(array $options = []): GameRace {
        return GameRace::factory()->create($options);
    }
}
