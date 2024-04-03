<?php

namespace Tests\Traits;

use App\Flare\Models\WeeklyMonsterFight;

trait CreateWeeklyMonsterFight {

    public function createWeeklyMonsterFight(array $options = []): WeeklyMonsterFight {
        return WeeklyMonsterFight::factory()->create($options);
    }
}
