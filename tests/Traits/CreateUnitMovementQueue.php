<?php

namespace Tests\Traits;

use App\Flare\Models\UnitMovementQueue;

trait CreateUnitMovementQueue
{
    public function createUnitMovementQueue(array $options = []): UnitMovementQueue
    {
        return UnitMovementQueue::factory()->create($options);
    }
}
