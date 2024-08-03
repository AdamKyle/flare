<?php

namespace Tests\Traits;

use App\Flare\Models\KingdomBuilding;

trait CreateKingdomBuilding
{
    public function createKingdomBuilding(array $options = []): KingdomBuilding
    {
        return KingdomBuilding::factory()->create($options);
    }
}
