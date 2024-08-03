<?php

namespace Tests\Traits;

use App\Flare\Models\GameSkill;

trait CreateGameSkill
{
    public function createGameSkill(array $options = []): GameSkill
    {
        return GameSkill::factory()->create($options);
    }
}
