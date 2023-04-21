<?php

namespace Tests\Traits;

use App\Flare\Models\Gem;

trait CreateGem {

    public function createGem(array $options = []): Gem {
        return Gem::factory()->create($options);
    }
}
