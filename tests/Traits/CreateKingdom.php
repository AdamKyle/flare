<?php

namespace Tests\Traits;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;

trait CreateKingdom {

    public function createKingdom(array $options = []): Kingdom {
        return Kingdom::factory()->create($options);
    }

    public function createKingdomUnit(array $options): KingdomUnit {
        return KingdomUnit::factory()->create($options);
    }
}
