<?php

namespace Tests\Traits;

use App\Flare\Models\Gem;

trait CreateGem
{
    public function createGem(array $options = []): Gem
    {
        return Gem::factory()->create($options);
    }

    public function createMapGeneratedGem($profile, array $options = []): Gem
    {
        return Gem::factory()->mapGenerated($profile)->create($options);
    }

    public function createLocationGeneratedGem($profile, array $options = []): Gem
    {
        return Gem::factory()->locationGenerated($profile)->create($options);
    }
}
