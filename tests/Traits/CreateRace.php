<?php

namespace Tests\Traits;

use App\Flare\Models\GameRace;

trait CreateRace {

    public function createRace(array $options = []) {
        return factory(GameRace::class)->create($options);
    }
}
