<?php

namespace Tests\Traits;

use App\Flare\Models\KingdomBuildingInQueue;
use App\Flare\Models\GameKingdomBuilding;

trait CreateGameKingdomBuilding {

    public function createGameKingdomBuilding(array $options = []): GameKingdomBuilding {
        return GameKingdomBuilding::factory()->create($options);
    }

    public function createKingdomBuildingQueue(array $options = []): KingdomBuildingInQueue {
        return KingdomBuildingInQueue::factory()->create($options);
    }
}
