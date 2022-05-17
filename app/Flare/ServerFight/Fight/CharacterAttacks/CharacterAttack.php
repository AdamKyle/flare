<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Flare\ServerFight\Monster\ServerMonster;

class CharacterAttack {

    private WeaponType $weaponType;

    public function __construct(WeaponType $weaponType) {
        $this->weaponType = $weaponType;
    }

    public function attack(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack {
        $this->weaponType->setCharacterHealth($characterHealth)
                         ->setMonsterHealth($monsterHealth)
                         ->setCharacterAttackData($character, $isPlayerVoided)
                         ->doWeaponAttack($character, $monster);

        return $this;
    }

    public function getMessages() {
        return $this->weaponType->getMessages();
    }

    public function resetMessages() {
        $this->weaponType->resetMessages();
    }

    public function getCharacterHealth() {
        return $this->weaponType->getCharacterHealth();
    }

    public function getMonsterHealth() {
        return $this->weaponType->getMonsterHealth();
    }
}
