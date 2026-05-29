<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterPassiveSkill;

trait CreateCharacterPassiveSkill
{
    public function createCharacterPassiveSkill(array $options = []): CharacterPassiveSkill
    {
        return CharacterPassiveSkill::factory()->create($options);
    }
}
