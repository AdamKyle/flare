<?php

namespace Tests\Traits;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\GameBuilding;

trait CreateGameBuilding {

    public function createGameBuilding(array $options = []): GameBuilding {
        return GameBuilding::factory()->create($options);
    }

    public function createBuildingQueue(array $options = []): BuildingInQueue {
        return BuildingInQueue::factory()->create($options);
    }
}
