<?php

namespace Tests\Traits;

use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;
use Illuminate\Database\Eloquent\Collection;

trait CreateGameUnit
{
    public function createGameUnit(array $options = []): GameUnit
    {
        return GameUnit::factory()->create($options);
    }

    public function createGameUnits(array $options = [], int $amount = 1): Collection
    {
        return GameUnit::factory()->count($amount)->create($options);
    }

    public function createUnitQueue(array $options = []): UnitInQueue
    {
        return UnitInQueue::factory()->create($options);
    }
}
