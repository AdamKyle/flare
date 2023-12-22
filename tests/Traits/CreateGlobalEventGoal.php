<?php

namespace Tests\Traits;

use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;

trait CreateGlobalEventGoal {

    public function createGlobalEventGoal(array $options = []): GlobalEventGoal {
        return GlobalEventGoal::factory()->create($options);
    }

    public function createGlobalEventKill(array $options = []): GlobalEventKill {
        return GlobalEventKill::factory()->create($options);
    }

    public function createGlobalEventParticipation(array $options = []): GlobalEventParticipation {
        return GlobalEventParticipation::factory()->create($options);
    }
}
