<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterBoon;

trait CreateCharacterBoon {

    public function createCharacterBoon(array $options = []): CharacterBoon {
        return CharacterBoon::factory()->create($options);
    }
}
