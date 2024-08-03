<?php

namespace Tests\Traits;

use App\Flare\Models\GameBuildingUnit;

trait CreateGameBuildingUnit
{
    public function createGameBuildingUnit(array $options): GameBuildingUnit
    {
        return GameBuildingUnit::factory()->create($options);
    }
}
