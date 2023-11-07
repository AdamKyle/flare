<?php

namespace Tests\Traits;

use App\Flare\Models\GlobalEventGoal;

trait CreateGlobalEventGoal {

    public function createGlobalEventGoal(array $options = []): GlobalEventGoal {
        return GlobalEventGoal::factory()->create($options);
    }
}
