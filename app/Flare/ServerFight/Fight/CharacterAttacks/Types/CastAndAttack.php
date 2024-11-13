<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Models\Character;

use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class CastAndAttack extends AttackAndCast
{
    public function __construct(
        CharacterCacheData $characterCacheData,
        Entrance $entrance,
        WeaponType $weaponType,
        CastType $castType
    ) {
        parent::__construct($characterCacheData, $entrance, $weaponType, $castType);
    }

    public function setCharacterCastAndAttackkData(Character $character, bool $isVoided): AttackAndCast
    {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_cast_and_attack' : 'cast_and_attack');
        $this->isVoided = $isVoided;

        return $this;
    }

    public function handleAttack(Character $character, ServerMonster $monster)
    {

        $this->setWhichCastType('cast_and_attack');
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
