<?php

namespace Tests\Traits;

use App\Flare\Models\Faction;

trait CreateFaction
{
    public function createFaction(array $options = []): Faction
    {
        return Faction::create(array_merge([
            'current_level' => 1,
            'current_points' => 0,
            'points_needed' => 100,
            'maxed' => false,
            'title' => 'Sample Title',
        ], $options));
    }
}
