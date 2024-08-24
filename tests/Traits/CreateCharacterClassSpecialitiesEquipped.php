<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterClassSpecialtiesEquipped;

trait CreateCharacterClassSpecialitiesEquipped
{
    public function createCharacterClassRankSpecial(array $options = []): CharacterClassSpecialtiesEquipped
    {
        return CharacterClassSpecialtiesEquipped::factory()->create($options);
    }
}
