<?php

namespace Tests\Traits;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\GameBuilding;

trait CreateGameBuilding
{
    public function createGameBuilding(array $options = []): GameBuilding
    {
        return GameBuilding::factory()->create($options);
    }

    public function createKingdomBuildingQueue(array $options = []): BuildingInQueue
    {
        return BuildingInQueue::factory()->create($options);
    }
}
