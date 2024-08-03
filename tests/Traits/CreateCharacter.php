<?php

namespace Tests\Traits;

use App\Flare\Models\Character;

trait CreateCharacter
{
    public function createCharacter(array $options = []): Character
    {
        return Character::factory()->create($options);
    }
}
