<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;

class CharacterInformationBuilder {

    public function setCharacter(Character $character): CharacterInformationBuilder {
        $this->character = $character;

        return $this;
    }

    public function buildAttack(): int {
        return $this->character->{$this->character->damage_stat} + 10;
    }

    public function buildHealth(): int {
        return $this->character->dur + 10;
    }
}
