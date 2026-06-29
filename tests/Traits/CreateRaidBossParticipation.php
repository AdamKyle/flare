<?php

namespace Tests\Traits;

use App\Flare\Models\RaidBossParticipation;

trait CreateRaidBossParticipation
{
    public function createRaidBossParticipation(array $options = []): RaidBossParticipation
    {
        return RaidBossParticipation::factory()->create($options);
    }
}
