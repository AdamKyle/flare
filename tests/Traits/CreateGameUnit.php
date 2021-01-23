<?php

namespace Tests\Traits;

use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;

trait CreateGameUnit {

    public function createGameUnit(array $options = []): GameUnit {
        return GameUnit::factory()->create($options);
    }

    public function createUnitQueue(array $options = []): UnitInQueue {
        return UnitInQueue::factory()->create($options);
    }
}
