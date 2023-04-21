<?php

namespace Tests\Traits;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterInCelestialFight;

trait CreateCelestials {

    public function createCelestialFight(array $options = []): CelestialFight {
        return CelestialFight::factory()->create($options);
    }

    public function createCharacterInCelestialFight(array $options = []): CharacterInCelestialFight {
        return CharacterInCelestialFight::factory()->create($options);
    }
}
