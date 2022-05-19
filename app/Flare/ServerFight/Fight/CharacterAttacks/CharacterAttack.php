<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Flare\ServerFight\Monster\ServerMonster;

class CharacterAttack {

    private WeaponType $weaponType;

    private CastType $castType;

    private mixed $type;

    public function __construct(WeaponType $weaponType, CastType $castType) {
        $this->weaponType = $weaponType;
        $this->castType   = $castType;
    }

    public function attack(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack {
        $this->weaponType->setCharacterHealth($characterHealth)
                         ->setMonsterHealth($monsterHealth)
                         ->setCharacterAttackData($character, $isPlayerVoided)
                         ->doWeaponAttack($character, $monster);

        $this->type = $this->weaponType;

        return $this;
    }

    public function cast(Character $character, ServerMonster $monster, bool $isPlayerVoided, int $characterHealth, int $monsterHealth): CharacterAttack {
        $this->castType->setCharacterHealth($characterHealth)
                         ->setMonsterHealth($monsterHealth)
                         ->setCharacterAttackData($character, $isPlayerVoided)
                         ->castAttack($character, $monster);

        $this->type = $this->castType;

        return $this;
    }

    public function getMessages() {
        return $this->type->getMessages();
    }

    public function resetMessages() {
        $this->type->resetMessages();
    }

    public function getCharacterHealth() {
        return $this->type->getCharacterHealth();
    }

    public function getMonsterHealth() {
        return $this->type->getMonsterHealth();
    }
}
