<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class CastAndAttack extends AttackAndCast {

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit,
                                SecondaryAttacks $secondaryAttacks, WeaponType $weaponType, CastType $castType) {
        parent::__construct($characterCacheData, $entrance, $canHit, $secondaryAttacks, $weaponType, $castType);
    }

    public function handlePvpAttack(Character $attacker, Character $defender) {

        $this->handlePvpCastAttack($attacker, $defender);
        $this->handlePvpWeaponAttack($attacker, $defender);

        $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($attacker, 'affix_damage_reduction'), true);

        return $this;
    }

    public function handleAttack(Character $character, ServerMonster $monster) {
        $this->handleCastAttack($character, $monster);
        $this->handleWeaponAttack($character, $monster);
        $this->secondaryAttack($character, $monster);

        return $this;
    }
}
