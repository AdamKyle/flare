<?php

namespace Tests\Traits;

use App\Flare\Models\Character;

trait CreateCharacter {

    public function createCharacter(array $options = []) {
        return factory(Character::class)->create($options);
    }
}
