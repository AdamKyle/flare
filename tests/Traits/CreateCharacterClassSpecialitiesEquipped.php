<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use Database\Factories\CharacterClassRankFactory;
use Database\Factories\CharacterClassSpecialtiesEquippedFactory;

trait CreateCharacterClassSpecialitiesEquipped {

    public function createCharacterClassRankSpecial(array $options = []): CharacterClassSpecialtiesEquipped {
        return CharacterClassSpecialtiesEquipped::factory()->create($options);
    }
}
