<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class CastAndAttack extends AttackAndCast
{
    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit,
        SecondaryAttacks $secondaryAttacks, WeaponType $weaponType, CastType $castType)
    {
        parent::__construct($characterCacheData, $entrance, $canHit, $secondaryAttacks, $weaponType, $castType);
    }

    public function setCharacterCastAndAttackkData(Character $character, bool $isVoided): AttackAndCast
    {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_cast_and_attack' : 'cast_and_attack');
        $this->isVoided = $isVoided;

        return $this;
    }

    public function handlePvpAttack(Character $attacker, Character $defender)
    {

        $this->handlePvpCastAttack($attacker, $defender);
        $this->handlePvpWeaponAttack($attacker, $defender);

        $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($attacker, 'affix_damage_reduction'), true);

        return $this;
    }

    public function handleAttack(Character $character, ServerMonster $monster)
    {

        $this->handleCastAttack($character, $monster, false);

        if ($this->characterHealth <= 0) {
            return $this;
        }

        $this->handleWeaponAttack($character, $monster);

        if ($this->characterHealth <= 0) {
            return $this;
        }

        return $this;
    }
}
